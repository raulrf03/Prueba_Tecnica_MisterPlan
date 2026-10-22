<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validators;

use App\Validators\InputValidator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/** Comprueba tipos, formatos y límites sin usar HTTP ni base de datos. */
final class InputValidatorTest extends TestCase
{
  /** Comprueba que requireString devuelve el texto sin espacios exteriores. */
  public function testTextoValidoEliminaEspaciosExteriores(): void
  {
    self::assertSame(
      'Raúl Gómez',
      InputValidator::requireString(
        ['nombre' => '  Raúl Gómez  '],
        'nombre'
      )
    );
  }

  /** Comprueba que requireString rechaza valores vacíos tras recortarlos. */
  public function testRechazaTextoCompuestoSoloDeEspacios(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireString(
      ['nombre' => '   '],
      'nombre'
    );
  }

  /** Comprueba que requireString no acepta arrays como texto. */
  public function testRechazaUnArrayComoTexto(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireString(
      ['nombre' => ['Raúl']],
      'nombre'
    );
  }

  /** Comprueba que requireString no acepta valores booleanos. */
  public function testRechazaUnBooleanoComoTexto(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireString(
      ['nombre' => true],
      'nombre'
    );
  }

  /** Comprueba que requireString aplica el máximo de caracteres indicado. */
  public function testRechazaTextoQueSuperaElLimite(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireString(
      ['nombre' => 'abcdef'],
      'nombre',
      5
    );
  }

  /** Comprueba que requireString rechaza bytes nulos en el texto. */
  public function testRechazaBytesNulosEnTexto(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireString(
      ['nombre' => "Raúl\0Gómez"],
      'nombre'
    );
  }

  /** Comprueba que optionalString devuelve null si el valor falta o queda vacío. */
  public function testNotasAusentesNulasOVaciasDevuelvenNull(): void
  {
    self::assertNull(
      InputValidator::optionalString([], 'notas')
    );

    self::assertNull(
      InputValidator::optionalString(['notas' => null], 'notas')
    );

    self::assertNull(
      InputValidator::optionalString(['notas' => '   '], 'notas')
    );
  }

  /** Comprueba que optionalString rechaza valores que no sean cadenas. */
  public function testRechazaUnObjetoComoNotas(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::optionalString(
      ['notas' => (object) ['texto' => 'Llegada tarde']],
      'notas'
    );
  }

  /** Comprueba que requireEmail conserva una dirección válida. */
  public function testAceptaUnEmailValido(): void
  {
    self::assertSame(
      'raul@example.com',
      InputValidator::requireEmail(
        ['email' => 'raul@example.com'],
        'email'
      )
    );
  }

  /** Comprueba que requireEmail rechaza direcciones con formato inválido. */
  public function testRechazaUnEmailInvalido(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireEmail(
      ['email' => 'correo-no-valido'],
      'email'
    );
  }

  /** Comprueba que requireDate acepta el 29 de febrero de un año bisiesto. */
  public function testAceptaUnaFechaBisiestaValida(): void
  {
    self::assertSame(
      '2028-02-29',
      InputValidator::requireDate(
        ['fecha' => '2028-02-29'],
        'fecha'
      )
    );
  }

  /** Comprueba que requireDate rechaza una fecha de calendario imposible. */
  public function testRechazaUnaFechaImposible(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireDate(
      ['fecha' => '2027-02-29'],
      'fecha'
    );
  }

  /** Comprueba que requireDate exige el formato AAAA-MM-DD. */
  public function testRechazaUnaFechaConFormatoIncorrecto(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireDate(
      ['fecha' => '22/10/2026'],
      'fecha'
    );
  }

  /** Comprueba que requireDateTime acepta una fecha y hora con segundos. */
  public function testAceptaUnaFechaYHoraValidas(): void
  {
    self::assertSame(
      '2026-10-22 20:30:00',
      InputValidator::requireDateTime(
        ['fecha' => '2026-10-22 20:30:00'],
        'fecha'
      )
    );
  }

  /** Comprueba que requireDateTime rechaza horas fuera del rango válido. */
  public function testRechazaUnaHoraImposible(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireDateTime(
      ['fecha' => '2026-10-22 25:30:00'],
      'fecha'
    );
  }

  /** Comprueba que requirePositiveInt acepta enteros y cadenas dentro del límite. */
  public function testAceptaEnterosPositivosComoNumeroOTexto(): void
  {
    self::assertSame(
      2,
      InputValidator::requirePositiveInt(
        ['personas' => 2],
        'personas',
        255
      )
    );

    self::assertSame(
      255,
      InputValidator::requirePositiveInt(
        ['personas' => '255'],
        'personas',
        255
      )
    );
  }

  /** Comprueba que requirePositiveInt rechaza el cero. */
  public function testRechazaCeroComoEnteroPositivo(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requirePositiveInt(
      ['personas' => 0],
      'personas',
      255
    );
  }

  /** Comprueba que requirePositiveInt aplica el máximo indicado. */
  public function testRechazaPersonasPorEncimaDelLimite(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requirePositiveInt(
      ['personas' => 256],
      'personas',
      255
    );
  }

  /** Comprueba que requirePositiveInt no convierte booleanos a enteros. */
  public function testRechazaUnBooleanoComoEntero(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requirePositiveInt(
      ['personas' => true],
      'personas'
    );
  }

  /** Comprueba que requirePositiveInt rechaza floats aunque no tengan decimales. */
  public function testRechazaUnFloatAunqueNoTengaFraccion(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requirePositiveInt(
      ['personas' => 2.0],
      'personas'
    );
  }

  /** Comprueba la normalización a dos decimales sin usar coma flotante. */
  public function testNormalizaImportesSinConvertirlosAFloat(): void
  {
    self::assertSame(
      '0.00',
      InputValidator::requireAmount(
        ['importe' => '0'],
        'importe'
      )
    );

    self::assertSame(
      '300.00',
      InputValidator::requireAmount(
        ['importe' => 300],
        'importe'
      )
    );

    self::assertSame(
      '300.50',
      InputValidator::requireAmount(
        ['importe' => '300.5'],
        'importe'
      )
    );

    self::assertSame(
      '99999999.99',
      InputValidator::requireAmount(
        ['importe' => '99999999.99'],
        'importe'
      )
    );
  }

  /** Comprueba que requireAmount rechaza importes negativos. */
  public function testRechazaUnImporteNegativo(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireAmount(
      ['importe' => '-0.01'],
      'importe'
    );
  }

  /** Comprueba que requireAmount admite como máximo dos posiciones decimales. */
  public function testRechazaImportesConMasDeDosDecimales(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireAmount(
      ['importe' => '100.123'],
      'importe'
    );
  }

  /** Comprueba que requireAmount respeta el límite de DECIMAL(10,2). */
  public function testRechazaImportesFueraDelLimite(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireAmount(
      ['importe' => '100000000.00'],
      'importe'
    );
  }

  /** Comprueba que requireAmount rechaza valores enviados como float. */
  public function testRechazaImportesEnComaFlotante(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireAmount(
      ['importe' => 100.50],
      'importe'
    );
  }

  /** Comprueba que requireEstado acepta los tres estados definidos. */
  public function testAceptaLosEstadosConocidos(): void
  {
    foreach (['pendiente', 'confirmada', 'cancelada'] as $estado) {
      self::assertSame(
        $estado,
        InputValidator::requireEstado(
          ['estado' => $estado],
          'estado'
        )
      );
    }
  }

  /** Comprueba que requireEstado rechaza valores fuera de la lista permitida. */
  public function testRechazaUnEstadoDesconocido(): void
  {
    $this->expectException(InvalidArgumentException::class);

    InputValidator::requireEstado(
      ['estado' => 'borrada'],
      'estado'
    );
  }
}
