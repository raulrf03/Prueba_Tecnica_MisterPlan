<?php

declare(strict_types=1);

namespace App\Security;

use RuntimeException;

final class Csrf
{
  public static function obtenerToken(): string
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }

    if (!isset($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
  }

  public static function validarPeticion(): void
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }

    $tokenEnviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    $tokenValido = isset($_SESSION['csrf_token'])
      && is_string($tokenEnviado)
      && hash_equals($_SESSION['csrf_token'], $tokenEnviado);

    $origen = $_SERVER['HTTP_ORIGIN'] ?? null;
    $hostPermitido = $_SERVER['HTTP_HOST'] ?? '';

    $origenValido = $origen === null
      || str_ends_with((string) $origen, '//' . $hostPermitido);

    if (!$tokenValido || !$origenValido) {
      throw new RuntimeException('Petición no autorizada.');
    }
  }
}
