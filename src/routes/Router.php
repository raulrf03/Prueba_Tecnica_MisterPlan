<?php

declare(strict_types=1);

namespace App\Routes;

final class Router
{
  public static function obtenerRuta(): string
  {
    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

    $ruta = is_string($ruta) ? $ruta : '/';

    return rtrim($ruta, '/') ?: '/';
  }

  public static function esRutaApi(string $ruta): bool
  {
    return $ruta === '/api' || str_starts_with($ruta, '/api/');
  }

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

  public static function responderError(string $mensaje, int $codigoEstado): void
  {
    self::responderJson(
      ['error' => $mensaje],
      $codigoEstado
    );
  }
}
