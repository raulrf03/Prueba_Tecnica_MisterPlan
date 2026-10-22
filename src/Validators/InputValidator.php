<?php

declare(strict_types=1);

namespace App\Validators;

use DateTimeImmutable;
use InvalidArgumentException;

/** Valida tipos, formatos y límites de los datos de entrada. */
class InputValidator
{
  /** Recorta una cadena UTF-8 obligatoria y comprueba que no supere el límite. */
  public static function requireString(
    array $datos,
    string $campo,
    int $longitudMaxima = 255
  ): string {
    $valor = $datos[$campo] ?? null;

    if ($valor === null) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' es obligatorio."
      );
    }

    if (!is_string($valor)) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe ser texto."
      );
    }

    // Evita aceptar texto mal codificado o bytes nulos.
    if (
      !mb_check_encoding($valor, 'UTF-8')
      || str_contains($valor, "\0")
    ) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' contiene texto no válido."
      );
    }

    $valor = trim($valor);

    if ($valor === '') {
      throw new InvalidArgumentException(
        "El campo '{$campo}' es obligatorio."
      );
    }

    if (mb_strlen($valor, 'UTF-8') > $longitudMaxima) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' no puede superar "
          . "{$longitudMaxima} caracteres."
      );
    }

    return $valor;
  }

  /** Valida una cadena opcional y devuelve null si falta o queda vacía al recortarla. */
  public static function optionalString(
    array $datos,
    string $campo,
    int $longitudMaxima = 5000
  ): ?string {
    if (!array_key_exists($campo, $datos) || $datos[$campo] === null) {
      return null;
    }

    $valor = $datos[$campo];

    if (!is_string($valor)) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe ser texto."
      );
    }

    if (
      !mb_check_encoding($valor, 'UTF-8')
      || str_contains($valor, "\0")
    ) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' contiene texto no válido."
      );
    }

    $valor = trim($valor);

    if (mb_strlen($valor, 'UTF-8') > $longitudMaxima) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' no puede superar "
          . "{$longitudMaxima} caracteres."
      );
    }

    return $valor === '' ? null : $valor;
  }

  /** Valida una dirección de correo obligatoria y devuelve el texto recortado. */
  public static function requireEmail(
    array $datos,
    string $campo
  ): string {
    $valor = self::requireString($datos, $campo, 180);

    if (filter_var($valor, FILTER_VALIDATE_EMAIL) === false) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' no contiene un email válido."
      );
    }

    return $valor;
  }

  /** Comprueba que el valor sea una fecha real en formato AAAA-MM-DD. */
  public static function requireDate(
    array $datos,
    string $campo
  ): string {
    $valor = self::requireString($datos, $campo, 10);

    if (preg_match('/\A[0-9]{4}-[0-9]{2}-[0-9]{2}\z/', $valor) !== 1) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe tener el formato AAAA-MM-DD."
      );
    }

    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $valor);

    if (
      $fecha === false
      || $fecha->format('Y-m-d') !== $valor
      || (int) $fecha->format('Y') < 1000
    ) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe contener una fecha válida "
          . "con año entre 1000 y 9999."
      );
    }

    return $valor;
  }

  /** Comprueba que el valor sea una fecha y hora real con segundos. */
  public static function requireDateTime(
    array $datos,
    string $campo
  ): string {
    $valor = self::requireString($datos, $campo, 19);

    if (
      preg_match(
        '/\A[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}\z/',
        $valor
      ) !== 1
    ) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe tener el formato "
          . "AAAA-MM-DD HH:MM:SS."
      );
    }

    $fecha = DateTimeImmutable::createFromFormat(
      '!Y-m-d H:i:s',
      $valor
    );

    if (
      $fecha === false
      || $fecha->format('Y-m-d H:i:s') !== $valor
      || (int) $fecha->format('Y') < 1000
    ) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe contener una fecha y hora válidas "
          . "con año entre 1000 y 9999."
      );
    }

    return $valor;
  }

  /** Valida un entero mayor que cero y no superior al máximo indicado. */
  public static function requirePositiveInt(
    array $datos,
    string $campo,
    int $maximo = PHP_INT_MAX
  ): int {
    $valor = $datos[$campo] ?? null;

    // No se aceptan booleanos ni floats, aunque puedan convertirse a entero.
    if (!is_int($valor) && !is_string($valor)) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe ser un número entero positivo."
      );
    }

    if (is_string($valor)) {
      $valor = trim($valor);
    }

    if (preg_match('/\A[1-9][0-9]*\z/', (string) $valor) !== 1) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe ser un número entero positivo."
      );
    }

    $entero = filter_var(
      $valor,
      FILTER_VALIDATE_INT,
      [
        'options' => [
          'min_range' => 1,
          'max_range' => $maximo,
        ],
      ]
    );

    if ($entero === false) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe estar entre 1 y {$maximo}."
      );
    }

    return $entero;
  }

  /** Valida y normaliza un importe DECIMAL(10,2) sin convertirlo a float. */
  public static function requireAmount(
    array $datos,
    string $campo
  ): string {
    $valor = $datos[$campo] ?? null;

    if (!is_int($valor) && !is_string($valor)) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe enviarse como entero "
          . "o texto decimal, por ejemplo \"300.50\"."
      );
    }

    $valor = trim((string) $valor);

    if (
      preg_match(
        '/\A(?:0|[1-9][0-9]{0,7})(?:\.[0-9]{1,2})?\z/',
        $valor
      ) !== 1
    ) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe estar entre 0 y 99999999.99, "
          . "con hasta dos decimales y punto como separador."
      );
    }

    $partes = explode('.', $valor, 2);
    $entera = $partes[0];
    $decimal = str_pad($partes[1] ?? '', 2, '0');

    return $entera . '.' . $decimal;
  }

  /** Valida que el valor sea uno de los estados admitidos por la aplicación. */
  public static function requireEstado(
    array $datos,
    string $campo
  ): string {
    $valor = self::requireString($datos, $campo, 20);

    $estadosValidos = ['pendiente', 'confirmada', 'cancelada'];

    if (!in_array($valor, $estadosValidos, true)) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' contiene un estado no válido."
      );
    }

    return $valor;
  }
}
