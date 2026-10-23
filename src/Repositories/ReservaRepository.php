<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Database\Database;
use DateTimeImmutable;
use PDO;
use Throwable;
use App\Services\ReservaEstadoService;

final class ReservaRepository
{
  private PDO $conexion;

  /** Permite inyectar una conexión en pruebas; en producción usa la conexión compartida. */
  public function __construct(?PDO $conexion = null)
  {
    $this->conexion = $conexion ?? Database::getConnection();
  }

  /** Recupera todas las reservas ordenadas por fecha de entrada e identificador. */
  public function obtenerTodas(): array
  {
    $sentencia = $this->conexion->prepare(
      'SELECT id, nombre_huesped, email_huesped, nombre_alojamiento,
                    fecha_entrada, fecha_salida, num_personas,
                  importe, estado, notas, fecha_creacion
            FROM reserva
            ORDER BY fecha_entrada ASC, id ASC'
    );

    $sentencia->execute();

    return $sentencia->fetchAll();
  }

  /** Devuelve el número total de reservas registradas. */
  public function contarTodas(): int
  {
    $sentencia = $this->conexion->query('SELECT COUNT(*) AS total FROM reserva');

    $resultado = $sentencia->fetch();

    return (int) ($resultado['total'] ?? 0);
  }

  /** Busca una reserva por ID; devuelve null si no existe. */
  public function obtenerPorId(int $id): ?array
  {
    $sentencia = $this->conexion->prepare(
      'SELECT id, nombre_huesped, email_huesped, nombre_alojamiento,
                    fecha_entrada, fecha_salida, num_personas,
                  importe, estado, notas, fecha_creacion
            FROM reserva
            WHERE id = :id'
    );

    $sentencia->execute(['id' => $id]);

    $reserva = $sentencia->fetch();

    return $reserva === false ? null : $reserva;
  }

  /** Recupera el historial de eventos de una reserva en orden cronológico. */
  public function obtenerEventosPorReservaId(int $id): array
  {
    $sentencia = $this->conexion->prepare(
      'SELECT id, reserva_id, tipo, descripcion, fecha_creacion
            FROM reserva_evento
            WHERE reserva_id = :id
        ORDER BY fecha_creacion ASC, id ASC'
    );

    $sentencia->execute(['id' => $id]);

    return $sentencia->fetchAll();
  }

  /**
   * Aplica únicamente los filtros proporcionados y enlaza sus valores como parámetros.
   */
  public function buscar(array $filtros): array
  {
    $condiciones = [];
    $parametros = [];

    if (isset($filtros['estado'])) {
      $condiciones[] = 'estado = :estado';
      $parametros['estado'] = $filtros['estado'];
    }

    if (isset($filtros['fecha_desde'])) {
      $condiciones[] = 'fecha_entrada >= :fecha_desde';
      $parametros['fecha_desde'] = $filtros['fecha_desde'];
    }

    if (isset($filtros['fecha_hasta'])) {
      $condiciones[] = 'fecha_entrada <= :fecha_hasta';
      $parametros['fecha_hasta'] = $filtros['fecha_hasta'];
    }

    if (isset($filtros['huesped'])) {
      $condiciones[] = 'nombre_huesped LIKE :huesped';
      $parametros['huesped'] = '%' . $filtros['huesped'] . '%';
    }

    $sql = 'SELECT id, nombre_huesped, email_huesped, nombre_alojamiento,
                      fecha_entrada, fecha_salida, num_personas,
                importe, estado, notas, fecha_creacion
                FROM reserva';

    if ($condiciones !== []) {
      $sql .= ' WHERE ' . implode(' AND ', $condiciones);
    }

    $sql .= ' ORDER BY fecha_entrada ASC, id ASC';

    $sentencia = $this->conexion->prepare($sql);
    $sentencia->execute($parametros);

    return $sentencia->fetchAll();
  }

  /** Guarda la reserva y su evento de creación como una única operación. */
  public function crear(array $datos): int
  {
    $this->conexion->beginTransaction();

    try {
      $sentencia = $this->conexion->prepare(
        'INSERT INTO reserva (
                    nombre_huesped,
                    email_huesped,
                    nombre_alojamiento,
                    fecha_entrada,
                    fecha_salida,
                    num_personas,
                    importe,
                    estado,
                    notas,
                    fecha_creacion
                ) VALUES (
                    :nombre_huesped,
                    :email_huesped,
                    :nombre_alojamiento,
                    :fecha_entrada,
                    :fecha_salida,
                    :num_personas,
                    :importe,
                    :estado,
                    :notas,
                    :fecha_creacion
                )'
      );

      $sentencia->execute([
        'nombre_huesped' => $datos['nombre_huesped'],
        'email_huesped' => $datos['email_huesped'],
        'nombre_alojamiento' => $datos['nombre_alojamiento'],
        'fecha_entrada' => $datos['fecha_entrada'],
        'fecha_salida' => $datos['fecha_salida'],
        'num_personas' => $datos['num_personas'],
        'importe' => $datos['importe'],
        'estado' => $datos['estado'],
        'notas' => $datos['notas'],
        'fecha_creacion' => $datos['fecha_creacion'],
      ]);

      $reservaId = (int) $this->conexion->lastInsertId();

      $this->registrarEvento(
        $reservaId,
        'creación',
        'Reserva creada por ' . $datos['nombre_huesped'] . '.',
        $datos['fecha_creacion']
      );

      $this->conexion->commit();

      return $reservaId;
    } catch (Throwable $e) {
      // Evita conservar una reserva sin su evento si cualquiera de las escrituras falla.
      if ($this->conexion->inTransaction()) {
        $this->conexion->rollBack();
      }

      throw $e;
    }
  }

  /**
   * Bloquea la reserva, valida la transición y guarda el estado
   * junto con su evento dentro de la misma transacción.
   */
  public function cambiarEstado(int $id, string $nuevoEstado): ?array
  {
    $this->conexion->beginTransaction();

    try {
      // La lectura que decide el cambio debe estar dentro de la transacción.
      $sentencia = $this->conexion->prepare(
        'SELECT id, nombre_huesped, email_huesped, nombre_alojamiento,
                    fecha_entrada, fecha_salida, num_personas,
            importe, estado, notas, fecha_creacion
            FROM reserva
            WHERE id = :id
            FOR UPDATE'
      );

      $sentencia->execute(['id' => $id]);

      $reserva = $sentencia->fetch(PDO::FETCH_ASSOC);

      if ($reserva === false) {
        $this->conexion->commit();

        return null;
      }

      $requiereCambio = ReservaEstadoService::requiereCambio(
        $reserva['estado'],
        $nuevoEstado
      );

      if (!$requiereCambio) {
        // Repetir la operación no duplica eventos.
        $this->conexion->commit();

        return $reserva;
      }

      $actualizacion = $this->conexion->prepare(
        'UPDATE reserva
            SET estado = :estado
            WHERE id = :id'
      );

      $actualizacion->execute([
        'estado' => $nuevoEstado,
        'id' => $id,
      ]);

      [$tipo, $descripcion] = match ($nuevoEstado) {
        'confirmada' => [
          'confirmación',
          'Reserva confirmada por el alojamiento.',
        ],
        'cancelada' => [
          'cancelación',
          'Reserva cancelada.',
        ],
      };

      $this->registrarEvento(
        $id,
        $tipo,
        $descripcion,
        (new DateTimeImmutable())->format('Y-m-d H:i:s')
      );

      $reserva['estado'] = $nuevoEstado;

      $this->conexion->commit();

      return $reserva;
    } catch (Throwable $e) {
      if ($this->conexion->inTransaction()) {
        $this->conexion->rollBack();
      }

      throw $e;
    }
  }

  /** Inserta un evento dentro de la transacción activa del repositorio. */
  private function registrarEvento(
    int $reservaId,
    string $tipo,
    string $descripcion,
    string $fechaCreacion
  ): void {
    $sentencia = $this->conexion->prepare(
      'INSERT INTO reserva_evento (
                reserva_id,
                tipo,
                descripcion,
                fecha_creacion
            ) VALUES (
                :reserva_id,
                :tipo,
                :descripcion,
                :fecha_creacion
            )'
    );

    $sentencia->execute([
      'reserva_id' => $reservaId,
      'tipo' => $tipo,
      'descripcion' => $descripcion,
      'fecha_creacion' => $fechaCreacion,
    ]);
  }
}
