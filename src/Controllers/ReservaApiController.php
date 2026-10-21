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

  /** Valida los filtros recibidos por query string y devuelve las coincidencias. */
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
      // Las peticiones de escritura deben incluir un token CSRF válido.
      Csrf::validarPeticion();

      $contenido = file_get_contents('php://input');
      $datos = json_decode($contenido ?: '', true);

      if (!is_array($datos)) {
        Router::responderError('El cuerpo de la petición debe ser JSON válido.', 400);

        return;
      }

      // Cada campo se valida antes de construir los datos que se guardarán.
      $nombreHuesped = InputValidator::requireString($datos, 'nombre_huesped', 120);
      $emailHuesped = InputValidator::requireEmail($datos, 'email_huesped');
      $alojamiento = InputValidator::requireString($datos, 'alojamiento', 120);
      $fechaEntrada = InputValidator::requireDate($datos, 'fecha_entrada');
      $fechaSalida = InputValidator::requireDate($datos, 'fecha_salida');
      $numPersonas = InputValidator::requirePositiveInt($datos, 'num_personas');

      $importeTotal = filter_var(
        $datos['importe_total'] ?? null,
        FILTER_VALIDATE_FLOAT
      );

      if ($importeTotal === false || $importeTotal < 0) {
        Router::responderError(
          'El campo \'importe_total\' debe ser un número igual o mayor que cero.',
          400
        );

        return;
      }

      if ($fechaSalida <= $fechaEntrada) {
        Router::responderError(
          'La fecha de salida debe ser posterior a la fecha de entrada.',
          400
        );

        return;
      }

      // Las notas vacías se guardan como NULL para mantener uniforme el dato.
      $notas = isset($datos['notas']) && trim((string) $datos['notas']) !== ''
        ? trim((string) $datos['notas'])
        : null;

      $repositorio = new ReservaRepository();

      $reservaId = $repositorio->crear([
        'nombre_huesped' => $nombreHuesped,
        'email_huesped' => $emailHuesped,
        'alojamiento' => $alojamiento,
        'fecha_entrada' => $fechaEntrada,
        'fecha_salida' => $fechaSalida,
        'num_personas' => $numPersonas,
        'importe_total' => number_format($importeTotal, 2, '.', ''),
        'estado' => 'pendiente',
        'notas' => $notas,
        'fecha_creacion' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
      ]);

      $reserva = $repositorio->obtenerPorId($reservaId);

      Router::responderJson([
        'eservas.js' => 'Reserva creada correctamente.',
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

      $contenido = file_get_contents('php://input');
      $datos = json_decode($contenido ?: '', true);

      if (!is_array($datos)) {
        Router::responderError('El cuerpo de la petición debe ser JSON válido.', 400);

        return;
      }

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
}
