<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\Csrf;
use App\Security\CsrfException;
use PHPUnit\Framework\TestCase;

/** Comprueba el token CSRF y la validación del origen de la petición. */
final class CsrfTest extends TestCase
{
  private const TOKEN = 'test-csrf-token';

  private const CABECERAS = [
    'HTTP_HOST',
    'HTTPS',
    'HTTP_X_CSRF_TOKEN',
    'HTTP_ORIGIN',
  ];

  private array $servidorAnterior = [];
  private array $sesionAnterior = [];

  /** Prepara una petición válida antes de cada prueba. */
  protected function setUp(): void
  {
    parent::setUp();

    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }

    // Guarda el estado previo para no eliminar datos de otras pruebas.
    foreach (self::CABECERAS as $clave) {
      $this->servidorAnterior[$clave] = [
        'existe' => array_key_exists($clave, $_SERVER),
        'valor' => $_SERVER[$clave] ?? null,
      ];
    }

    $this->sesionAnterior = $_SESSION;

    $_SESSION = [
      'csrf_token' => self::TOKEN,
    ];

    $_SERVER['HTTP_HOST'] = 'localhost:8000';
    $_SERVER['HTTPS'] = 'off';
    $_SERVER['HTTP_X_CSRF_TOKEN'] = self::TOKEN;
    $_SERVER['HTTP_ORIGIN'] = 'http://localhost:8000';
  }

  /** Restaura los datos que existían antes de la prueba. */
  protected function tearDown(): void
  {
    foreach ($this->servidorAnterior as $clave => $estado) {
      if ($estado['existe']) {
        $_SERVER[$clave] = $estado['valor'];
      } else {
        unset($_SERVER[$clave]);
      }
    }

    $_SESSION = $this->sesionAnterior;

    parent::tearDown();
  }

  /** Acepta una petición con token y origen correctos. */
  public function testAceptaTokenYOrigenCoincidentes(): void
  {
    $this->expectNotToPerformAssertions();

    Csrf::validarPeticion();
  }

  /** Rechaza un origen que intenta engañar a una comparación por sufijo. */
  public function testRechazaOrigenQueSoloCoincideEnElSufijo(): void
  {
    $_SERVER['HTTP_ORIGIN'] =
      'http://attacker.invalid/path//localhost:8000';

    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }

  /** Rechaza un Origin con una ruta adicional. */
  public function testRechazaOrigenConRuta(): void
  {
    $_SERVER['HTTP_ORIGIN'] = 'http://localhost:8000/path';

    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }

  /** Rechaza HTTPS cuando el servidor se está simulando como HTTP. */
  public function testRechazaOrigenConEsquemaDiferente(): void
  {
    $_SERVER['HTTP_ORIGIN'] = 'https://localhost:8000';

    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }

  /** Rechaza una petición que no envía la cabecera del token. */
  public function testRechazaTokenAusente(): void
  {
    unset($_SERVER['HTTP_X_CSRF_TOKEN']);

    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }

  /** Rechaza un token distinto al almacenado en la sesión. */
  public function testRechazaTokenIncorrecto(): void
  {
    $_SERVER['HTTP_X_CSRF_TOKEN'] = 'token-incorrecto';

    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }

  /** Rechaza la petición si no existe un token guardado en la sesión. */
  public function testRechazaTokenAusenteEnSesion(): void
  {
    unset($_SESSION['csrf_token']);

    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }

  /** Rechaza otro host aunque coincidan el esquema y el puerto. */
  public function testRechazaHostDiferente(): void
  {
    $_SERVER['HTTP_ORIGIN'] = 'http://attacker.invalid:8000';

    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }

  /** Rechaza otro puerto aunque coincidan el esquema y el host. */
  public function testRechazaPuertoDiferente(): void
  {
    $_SERVER['HTTP_ORIGIN'] = 'http://localhost:8001';

    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }

  /**
   * Documenta la política actual:
   * Origin puede faltar, pero el token sigue siendo obligatorio.
   */
  public function testAceptaTokenValidoSinCabeceraOrigin(): void
  {
    unset($_SERVER['HTTP_ORIGIN']);

    $this->expectNotToPerformAssertions();

    Csrf::validarPeticion();
  }
}
