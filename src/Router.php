<?php
/** Contiene utilidades para consultar rutas y responder peticiones. */

declare(strict_types=1);

namespace App;

/** Agrupa operaciones comunes para las rutas de la aplicación. */
final class Router
{
  /** Devuelve la ruta solicitada sin la barra final. */
  public static function obtenerRuta(): string
  {
    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    $ruta = is_string($ruta) ? $ruta : '/';

    return rtrim($ruta, '/') ?: '/';
  }

  /** Indica si la ruta pertenece a la API. */
  public static function esRutaApi(string $ruta): bool
  {
    return $ruta === '/api' || str_starts_with($ruta, '/api/');
  }

  /** Envía una respuesta JSON y termina la petición. */
  public static function responderJson(array $datos, int $codigoEstado = 200): void
  {
    http_response_code($codigoEstado);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
      $datos,
      JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
    );
    exit;
  }

  /** Envía un error con formato JSON. */
  public static function responderError(string $mensaje, int $codigoEstado): void
  {
    self::responderJson(
      ['error' => $mensaje],
      $codigoEstado
    );
  }
}
