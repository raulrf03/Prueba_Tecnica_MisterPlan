<?php

declare(strict_types=1);

namespace App\Security;

final class Csrf
{
  public static function obtenerToken(): string
  {
    self::iniciarSesion();

    if (!isset($_SESSION['csrf_token'])) {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
  }

  public static function validarPeticion(): void
  {
    self::iniciarSesion();

    $tokenEnviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    $tokenSesion = $_SESSION['csrf_token'] ?? null;
    $tokenValido = \is_string($tokenSesion)
      && \is_string($tokenEnviado)
      && hash_equals($tokenSesion, $tokenEnviado);

    $origen = $_SERVER['HTTP_ORIGIN'] ?? null;
    $hostPermitido = $_SERVER['HTTP_HOST'] ?? '';

    // Origin puede faltar en clientes no navegador; el token sigue siendo obligatorio.
    $origenValido = $origen === null || self::esOrigenPermitido($origen, $hostPermitido);

    if (!$tokenValido || !$origenValido) {
      throw new CsrfException('Petición no autorizada.');
    }
  }

  private static function esOrigenPermitido(string $origen, string $hostPermitido): bool
  {
    // Un Origin solo admite esquema, host y puerto; no una ruta ni credenciales.
    $partesOrigen = parse_url($origen);
    $partesHost = parse_url('//' . $hostPermitido);

    if (
      !\is_array($partesOrigen)
      || !\is_array($partesHost)
      || !isset($partesOrigen['scheme'], $partesOrigen['host'], $partesHost['host'])
      || isset($partesOrigen['user'])
      || isset($partesOrigen['pass'])
      || isset($partesOrigen['path'])
      || isset($partesOrigen['query'])
      || isset($partesOrigen['fragment'])
    ) {
      return false;
    }

    $esquemaOrigen = strtolower($partesOrigen['scheme']);
    $esquemaEsperado = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'
      ? 'https'
      : 'http';

    if (!\in_array($esquemaOrigen, ['http', 'https'], true)) {
      return false;
    }

    $puertoOrigen = $partesOrigen['port'] ?? ($esquemaOrigen === 'https' ? 443 : 80);
    $puertoEsperado = $partesHost['port'] ?? ($esquemaEsperado === 'https' ? 443 : 80);

    return $esquemaOrigen === $esquemaEsperado
      && strcasecmp($partesOrigen['host'], $partesHost['host']) === 0
      && $puertoOrigen === $puertoEsperado;
  }

  private static function iniciarSesion(): void
  {
    if (session_status() === PHP_SESSION_ACTIVE) {
      return;
    }

    $parametrosCookie = session_get_cookie_params();
    $parametrosCookie['secure'] = !empty($_SERVER['HTTPS'])
      && strtolower((string) $_SERVER['HTTPS']) !== 'off';
    $parametrosCookie['httponly'] = true;
    $parametrosCookie['samesite'] = 'Lax';

    session_set_cookie_params($parametrosCookie);
    session_start();
  }
}
