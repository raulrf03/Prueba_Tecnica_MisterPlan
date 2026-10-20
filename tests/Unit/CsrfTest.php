<?php

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Security\Csrf;
use App\Security\CsrfException;
use PHPUnit\Framework\TestCase;

/** Pruebas unitarias de la protección CSRF. */
final class CsrfTest extends TestCase
{
  /** Token CSRF que se simula como si estuviera guardado en la sesión. */
  private const TOKEN = 'test-csrf-token';

  /**
   * Se ejecuta antes de cada test.
   *
   * Prepara una sesión con un token conocido y simula una petición
   * procedente de http://localhost:8000 con el token correcto.
   */
  protected function setUp(): void
  {
    parent::setUp();

    // Garantiza que existe una sesión activa para poder asignar el token.
    if (session_status() !== PHP_SESSION_ACTIVE) {
      session_start();
    }

    // Simula el token almacenado en la sesión del usuario.
    $_SESSION['csrf_token'] = self::TOKEN;

    // Simula el host desde el que se realiza la petición.
    $_SERVER['HTTP_HOST'] = 'localhost:8000';

    // Simula una conexión HTTP, no HTTPS.
    $_SERVER['HTTPS'] = 'off';

    // Simula la cabecera X-CSRF-Token enviada por el navegador.
    $_SERVER['HTTP_X_CSRF_TOKEN'] = self::TOKEN;

    // Simula la cabecera Origin de una petición legítima.
    $_SERVER['HTTP_ORIGIN'] = 'http://localhost:8000';
  }

  /**
   * Se ejecuta después de cada test.
   *
   * Limpia las superglobales para que un test no contamine al siguiente.
   */
  protected function tearDown(): void
  {
    unset(
      $_SERVER['HTTP_HOST'],
      $_SERVER['HTTPS'],
      $_SERVER['HTTP_X_CSRF_TOKEN'],
      $_SERVER['HTTP_ORIGIN']
    );

    $_SESSION = [];

    parent::tearDown();
  }

  /**
   * Comprueba que una petición con token y origen correctos no lanza excepción.
   */
  public function testAceptaTokenYOrigenCoincidentes(): void
  {
    // Indica a PHPUnit que el método no devuelve un valor assertion,
    // pero que no lanzar CsrfException ya es el resultado esperado.
    $this->expectNotToPerformAssertions();

    // Si el token o el origen fueran inválidos, este método lanzaría CsrfException.
    Csrf::validarPeticion();
  }

  /**
   * Comprueba que un origen malicioso no se acepta aunque su texto
   * contenga el host permitido como sufijo.
   */
  public function testRechazaOrigenQueSoloCoincideEnElSufijo(): void
  {
    // El origen intenta engañar a una comparación por sufijo:
    // contiene "localhost:8000", pero no es el origen legítimo.
    $_SERVER['HTTP_ORIGIN'] = 'http://attacker.invalid/path//localhost:8000';

    // Se espera que la validación rechace la petición.
    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }

  /**
   * Comprueba que un origen con ruta adicional se rechaza.
   *
   * Un Origin válido debe ser solo esquema, host y puerto:
   * http://localhost:8000, no http://localhost:8000/path.
   */
  public function testRechazaOrigenConRuta(): void
  {
    $_SERVER['HTTP_ORIGIN'] = 'http://localhost:8000/path';

    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }

  /**
   * Comprueba que no se acepta un cambio de esquema.
   *
   * El test simula HTTP, pero envía un Origin HTTPS; deben coincidir.
   */
  public function testRechazaOrigenConEsquemaDiferente(): void
  {
    $_SERVER['HTTP_ORIGIN'] = 'https://localhost:8000';

    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }

  /**
   * Comprueba que una petición sin cabecera X-CSRF-Token se rechaza,
   * aunque el origen sea válido.
   */
  public function testRechazaTokenAusente(): void
  {
    // Elimina la cabecera simulada para comprobar el caso de petición sin token.
    unset($_SERVER['HTTP_X_CSRF_TOKEN']);

    $this->expectException(CsrfException::class);

    Csrf::validarPeticion();
  }
}
