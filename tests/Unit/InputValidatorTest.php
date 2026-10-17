<?php
/** Comprueba los casos válidos y no válidos de cada validador. */

declare(strict_types=1);

namespace App\Tests\Unit\Validators;

use App\Validators\InputValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** Pruebas unitarias de las validaciones de entrada. */
class InputValidatorTest extends TestCase
{
  /** Comprueba que el texto se recorta y se devuelve. */
  public function test_requireString(): void
  {
    $this->assertSame(
      'Laura Gómez',
      InputValidator::requireString(['nombre' => '  Laura Gómez  '], 'nombre')
    );
  }

  /** Comprueba la aceptación de un correo válido. */
  public function test_requireEmail(): void
  {
    $this->assertSame(
      'laura.gomez@example.com',
      InputValidator::requireEmail(['email' => 'laura.gomez@example.com'], 'email')
    );
  }

  /** Comprueba la aceptación de una fecha válida. */
  public function test_requireDate(): void
  {
    $this->assertSame(
      '2026-11-10',
      InputValidator::requireDate(['fecha_entrada' => '2026-11-10'], 'fecha_entrada')
    );
  }

  /** Comprueba la aceptación de una fecha y hora válidas. */
  public function test_requireDateTime(): void
  {
    $this->assertSame(
      '2026-10-17 19:30:00',
      InputValidator::requireDateTime(['fecha_creacion' => '2026-10-17 19:30:00'], 'fecha_creacion')
    );
  }

  /** Comprueba la aceptación de un entero positivo. */
  public function test_requirePositiveInt(): void
  {
    $this->assertSame(
      2,
      InputValidator::requirePositiveInt(['num_personas' => '2'], 'num_personas')
    );
  }

  /** Comprueba la aceptación de un estado permitido. */
  public function test_requireEstado(): void
  {
    $this->assertSame(
      'pendiente',
      InputValidator::requireEstado(['estado' => 'pendiente'], 'estado')
    );
  }

  // --- Casos de fallo (uno por método) ---

  /** Rechaza un texto vacío. */
  public function test_requireString_campo_vacio(): void
  {
    $this->expectException(InvalidArgumentException::class);
    InputValidator::requireString(['nombre' => ''], 'nombre');
  }

  /** Rechaza una dirección de correo no válida. */
  public function test_requireEmail_invalido(): void
  {
    $this->expectException(InvalidArgumentException::class);
    InputValidator::requireEmail(['email' => 'no-es-email'], 'email');
  }

  /** Rechaza una fecha imposible. */
  public function test_requireDate_fecha_imposible(): void
  {
    $this->expectException(InvalidArgumentException::class);
    InputValidator::requireDate(['fecha_entrada' => '2026-02-30'], 'fecha_entrada');
  }

  /** Rechaza una fecha sin hora. */
  public function test_requireDateTime_solo_fecha(): void
  {
    $this->expectException(InvalidArgumentException::class);
    InputValidator::requireDateTime(['fecha_creacion' => '2026-10-17'], 'fecha_creacion');
  }

  /** Rechaza una hora sin segundos. */
  public function test_requireDateTime_sin_segundos(): void
  {
    $this->expectException(InvalidArgumentException::class);
    InputValidator::requireDateTime(
      ['fecha_creacion' => '2026-10-17 19:30'],
      'fecha_creacion'
    );
  }

  /** Rechaza el valor cero. */
  public function test_requirePositiveInt_cero(): void
  {
    $this->expectException(InvalidArgumentException::class);
    InputValidator::requirePositiveInt(['num_personas' => '0'], 'num_personas');
  }

  /** Rechaza un estado que no está permitido. */
  public function test_requireEstado_invalido(): void
  {
    $this->expectException(InvalidArgumentException::class);
    InputValidator::requireEstado(['estado' => 'pagada'], 'estado');
  }
}
