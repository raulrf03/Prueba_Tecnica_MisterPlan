<?php
/** Comprueba que los datos recibidos tengan el formato esperado. */

declare(strict_types=1);

namespace App\Validators;

use DateTimeImmutable;
use InvalidArgumentException;

/** Reúne validaciones usadas por la aplicación. */
class InputValidator
{
  /** Exige texto no vacío y dentro del límite indicado. */
  public static function requireString(
    array $datos,
    string $campo,
    int $longitudMaxima = 255
  ): string {
    $valor = trim((string) ($datos[$campo] ?? ''));

    if ($valor === '') {
      throw new InvalidArgumentException("El campo '{$campo}' es obligatorio.");
    }

    if (mb_strlen($valor) > $longitudMaxima) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' no puede superar {$longitudMaxima} caracteres."
      );
    }

    return $valor;
  }

  /** Exige una dirección de correo válida. */
  public static function requireEmail(array $datos, string $campo): string
  {
    $valor = self::requireString($datos, $campo, 180);

    if (!filter_var($valor, FILTER_VALIDATE_EMAIL)) {
      throw new InvalidArgumentException("El campo '{$campo}' no contiene un email válido.");
    }

    return $valor;
  }

  /** Exige una fecha válida con formato AAAA-MM-DD. */
  public static function requireDate(array $datos, string $campo): string
  {
    $valor = self::requireString($datos, $campo, 10);

    $fecha = DateTimeImmutable::createFromFormat('Y-m-d', $valor);

    if ($fecha === false || $fecha->format('Y-m-d') !== $valor) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe tener el formato AAAA-MM-DD."
      );
    }

    return $valor;
  }

  /** Exige una fecha y hora válidas, incluidos los segundos. */
  public static function requireDateTime(array $datos, string $campo): string
  {
    $valor = self::requireString($datos, $campo, 19);

    $fecha = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $valor);

    if ($fecha === false || $fecha->format('Y-m-d H:i:s') !== $valor) {
      throw new InvalidArgumentException(
        "El campo '{$campo}' debe tener el formato AAAA-MM-DD HH:MM:SS."
      );
    }

    return $valor;
  }

  /** Exige un número entero mayor que cero. */
  public static function requirePositiveInt(array $datos, string $campo): int
  {
    $valor = filter_var(
      $datos[$campo] ?? null,
      FILTER_VALIDATE_INT,
      ['options' => ['min_range' => 1]]
    );

    if ($valor === false) {
      throw new InvalidArgumentException("El campo '{$campo}' debe ser un número entero positivo.");
    }

    return $valor;
  }

  /** Comprueba que el estado esté entre los permitidos. */
  public static function requireEstado(array $datos, string $campo): string
  {
    $valor = self::requireString($datos, $campo, 20);

    $estadosValidos = ['pendiente', 'confirmada', 'cancelada'];

    if (!in_array($valor, $estadosValidos, true)) {
      throw new InvalidArgumentException("El campo '{$campo}' contiene un estado no válido.");
    }

    return $valor;
  }
}
