<?php
/** Comprueba el acceso y la reutilización de la conexión. */

declare(strict_types=1);

namespace App\Tests\Unit\Database;

use App\Database\Database;
use PHPUnit\Framework\TestCase;

/** Pruebas unitarias de la conexión a la base de datos. */
class DatabaseTest extends TestCase
{
    /** Comprueba que se devuelve una conexión PDO. */
    public function test_getConnection_devuelve_pdo(): void
    {
        $pdo = Database::getConnection();
        $this->assertInstanceOf(\PDO::class, $pdo);
    }

    /** Comprueba que las llamadas reutilizan la misma conexión. */
    public function test_getConnection_es_singleton(): void
    {
        $pdo1 = Database::getConnection();
        $pdo2 = Database::getConnection();
        $this->assertSame($pdo1, $pdo2);
    }
}   