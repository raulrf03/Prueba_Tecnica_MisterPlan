<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ReservaRepository;
use App\Routes\Router;
use App\Validators\InputValidator;
use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;
use App\Security\Csrf;
use App\Security\CsrfException;
use App\Exceptions\EstadoReservaException;

final class ReservaApiController
{
  /** Devuelve todas las reservas y el total registrado. */
  public function listar(): void
  {
    try {
      $repositorio = new ReservaRepository();

      Router::responderJson([
        'datos' => $repositorio->obtenerTodas(),
        'total' => $repositorio->contarTodas(),
      ]);
    } catch (Throwable $e) {
      // Se registra el detalle para depuración sin exponerlo en la respuesta pública.
      error_log(sprintf(
        'Error al listar reservas: %s en %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
      ));

      Router::responderError(
        'No se han podido obtener las reservas.',
        500
      );
    }
  }

  /** Devuelve una reserva junto con su historial de eventos. */
  public function obtener(int $id): void
  {
    try {
      $repositorio = new ReservaRepository();

      $reserva = $repositorio->obtenerPorId($id);

      if ($reserva === null) {
        Router::responderError('Reserva no encontrada.', 404);

        return;
      }

      $eventos = $repositorio->obtenerEventosPorReservaId($id);

      Router::responderJson([
        'datos' => $reserva,
        'eventos' => $eventos,
      ]);
    } catch (Throwable $e) {
      // El detalle técnico queda en el log; el cliente recibe un error genérico.
      error_log(sprintf(
        'Error al obtener la reserva %d: %s en %s:%d',
        $id,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
      ));

      Router::responderError(
        'No se ha podido obtener la reserva.',
        500
      );
    }
  }

  /** Valida los filtros y devuelve las reservas que coinciden. */
  public function buscar(): void
  {
    try {
      $filtros = [];

      if (isset($_GET['estado'])) {
        $filtros['estado'] = InputValidator::requireEstado(
          $_GET,
          'estado'
        );
      }

      if (isset($_GET['fecha_desde'])) {
        $filtros['fecha_desde'] = InputValidator::requireDate(
          $_GET,
          'fecha_desde'
        );
      }

      if (isset($_GET['fecha_hasta'])) {
        $filtros['fecha_hasta'] = InputValidator::requireDate(
          $_GET,
          'fecha_hasta'
        );
      }

      if (isset($_GET['huesped'])) {
        $filtros['huesped'] = InputValidator::requireString(
          $_GET,
          'huesped',
          120
        );
      }

      // Después de validar cada fecha, comprobamos el rango completo.
      if (
        isset($filtros['fecha_desde'], $filtros['fecha_hasta'])
        && $filtros['fecha_desde'] > $filtros['fecha_hasta']
      ) {
        throw new InvalidArgumentException(
          'La fecha desde no puede ser posterior a la fecha hasta.'
        );
      }

      $repositorio = new ReservaRepository();
      $reservas = $repositorio->buscar($filtros);

      Router::responderJson([
        'datos' => $reservas,
        'total' => count($reservas),
      ]);
    } catch (InvalidArgumentException $e) {
      Router::responderError($e->getMessage(), 400);
    } catch (Throwable $e) {
      error_log(sprintf(
        'Error al buscar reservas: %s en %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
      ));

      Router::responderError(
        'No se han podido buscar las reservas.',
        500
      );
    }
  }

  /** Valida y crea una reserva, devolviendo el recurso creado con estado HTTP 201. */
  public function crear(): void
  {
    try {
      Csrf::validarPeticion();

      $datos = $this->leerObjetoJson();

      $nombreHuesped = InputValidator::requireString(
        $datos,
        'nombre_huesped',
        120
      );

      $emailHuesped = InputValidator::requireEmail(
        $datos,
        'email_huesped'
      );

      $alojamiento = InputValidator::requireString(
        $datos,
        'alojamiento',
        120
      );

      $fechaEntrada = InputValidator::requireDate(
        $datos,
        'fecha_entrada'
      );

      $fechaSalida = InputValidator::requireDate(
        $datos,
        'fecha_salida'
      );

      $numPersonas = InputValidator::requirePositiveInt(
        $datos,
        'num_personas',
        255
      );

      $importeTotal = InputValidator::requireAmount(
        $datos,
        'importe_total'
      );

      $notas = InputValidator::optionalString(
        $datos,
        'notas',
        5000
      );

      $ahora = new DateTimeImmutable();
      $hoy = $ahora->format('Y-m-d');

      if ($fechaEntrada < $hoy) {
        throw new InvalidArgumentException(
          'La fecha de entrada no puede ser anterior a hoy.'
        );
      }

      if ($fechaSalida <= $fechaEntrada) {
        throw new InvalidArgumentException(
          'La fecha de salida debe ser posterior a la fecha de entrada.'
        );
      }

      $repositorio = new ReservaRepository();

      $reservaId = $repositorio->crear([
        'nombre_huesped' => $nombreHuesped,
        'email_huesped' => $emailHuesped,
        'alojamiento' => $alojamiento,
        'fecha_entrada' => $fechaEntrada,
        'fecha_salida' => $fechaSalida,
        'num_personas' => $numPersonas,
        'importe_total' => $importeTotal,
        'estado' => 'pendiente',
        'notas' => $notas,
        'fecha_creacion' => $ahora->format('Y-m-d H:i:s'),
      ]);

      $reserva = $repositorio->obtenerPorId($reservaId);

      header('Location: /api/reservas/' . $reservaId);

      Router::responderJson([
        'mensaje' => 'Reserva creada correctamente.',
        'datos' => $reserva,
      ], 201);
    } catch (InvalidArgumentException $e) {
      Router::responderError($e->getMessage(), 400);
    } catch (CsrfException $e) {
      Router::responderError($e->getMessage(), 403);
    } catch (Throwable $e) {
      error_log(sprintf(
        'Error al crear una reserva: %s en %s:%d',
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
      ));

      Router::responderError(
        'No se ha podido crear la reserva.',
        500
      );
    }
  }

  /** Cambia el estado de una reserva y devuelve su historial actualizado. */
  public function cambiarEstado(int $id): void
  {
    try {
      // El cambio de estado también es una operación protegida contra CSRF.
      Csrf::validarPeticion();

      $datos = $this->leerObjetoJson();

      $nuevoEstado = InputValidator::requireEstado($datos, 'estado');

      if ($nuevoEstado === 'pendiente') {
        // Una reserva solo puede avanzar a confirmada o cancelada.
        Router::responderError(
          'Una reserva no puede volver al estado pendiente.',
          400
        );

        return;
      }

      $repositorio = new ReservaRepository();

      $reserva = $repositorio->cambiarEstado($id, $nuevoEstado);

      if ($reserva === null) {
        Router::responderError('Reserva no encontrada.', 404);

        return;
      }

      $eventos = $repositorio->obtenerEventosPorReservaId($id);

      Router::responderJson([
        'mensaje' => 'Estado actualizado correctamente.',
        'datos' => $reserva,
        'eventos' => $eventos,
      ]);
    } catch (InvalidArgumentException $e) {
      Router::responderError($e->getMessage(), 400);
    } catch (CsrfException $e) {
      Router::responderError($e->getMessage(), 403);
    } catch (EstadoReservaException $e) {
      Router::responderError($e->getMessage(), 409);
    } catch (Throwable $e) {
      error_log(sprintf(
        'Error al cambiar el estado de la reserva %d: %s en %s:%d',
        $id,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
      ));

      Router::responderError(
        'No se ha podido cambiar el estado de la reserva.',
        500
      );
    }
  }
  /** Lee un objeto JSON y rechaza cuerpos vacíos, mal formados o de otro tipo. */
  private function leerObjetoJson(): array
  {
    $contenido = file_get_contents('php://input');

    if ($contenido === false || trim($contenido) === '') {
      throw new InvalidArgumentException(
        'El cuerpo de la petición debe ser un objeto JSON válido.'
      );
    }

    try {
      // Sin true, podemos distinguir un objeto JSON de una lista JSON.
      $objeto = json_decode(
        $contenido,
        false,
        32,
        JSON_THROW_ON_ERROR
      );
    } catch (\JsonException $e) {
      throw new InvalidArgumentException(
        'El cuerpo de la petición debe ser un objeto JSON válido.',
        0,
        $e
      );
    }

    if (!$objeto instanceof \stdClass) {
      throw new InvalidArgumentException(
        'El cuerpo de la petición debe ser un objeto JSON, no una lista ni un valor simple.'
      );
    }

    return get_object_vars($objeto);
  }
}
