<?php

declare(strict_types=1);

namespace App\Tests\Unit\Database;

use App\Database\Database;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Comprueba la conexión real a la base de datos.
 *
 * Requiere MySQL disponible y la configuración de conexión correcta.
 * No inserta, modifica ni elimina registros.
 */
final class DatabaseTest extends TestCase
{
  /** Comprueba que la conexión devuelve una instancia de PDO. */
  public function test_getConnection_devuelve_pdo(): void
  {
    $pdo = Database::getConnection();

    $this->assertInstanceOf(PDO::class, $pdo);
  }

  /** Comprueba que se reutiliza la misma instancia de conexión. */
  public function test_getConnection_es_singleton(): void
  {
    $primeraConexion = Database::getConnection();
    $segundaConexion = Database::getConnection();

    $this->assertSame(
      $primeraConexion,
      $segundaConexion
    );
  }
}
