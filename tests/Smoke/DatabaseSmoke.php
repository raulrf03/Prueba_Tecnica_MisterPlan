<?php
/** Comprueba de forma rápida que la base de datos responde. */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Database\Database;

$fallos = 0;

/** Ejecuta una comprobación y cuenta si falla. */
function verificar(string $nombre, callable $callback): void
{
    global $fallos;
    try {
        $callback();
        echo "  ✓ {$nombre}" . PHP_EOL;
    } catch (\Throwable $e) {
        $fallos++;
        echo "  ✗ {$nombre} → " . $e->getMessage() . PHP_EOL;
    }
}

echo "Smoke test - Database" . PHP_EOL;
echo str_repeat('─', 40) . PHP_EOL;

verificar('Conexión PDO', function () {
    $pdo = Database::getConnection();
    if (!$pdo instanceof \PDO) {
        throw new \RuntimeException('No devolvió un objeto PDO.');
    }
});

verificar('Query SELECT', function () {
    $pdo = Database::getConnection();
    $resultado = $pdo->query(
        'SELECT DATABASE() AS base_datos'
    )->fetch();

    if ($resultado === false) {
        throw new \RuntimeException('La query no devolvió resultados.');
    }

    if ($resultado['base_datos'] !== 'reservas') {
        throw new \RuntimeException(
            "Base de datos inesperada: {$resultado['base_datos']}"
        );
    }
});

echo str_repeat('─', 40) . PHP_EOL;

if ($fallos > 0) {
    echo "FALLIDO: {$fallos} test(s) no pasaron." . PHP_EOL;
    exit(1);
}

echo "OK: todos los tests de humo pasaron." . PHP_EOL;
exit(0);   