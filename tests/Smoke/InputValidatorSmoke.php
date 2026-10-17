<?php
/** Comprueba rápidamente los validadores principales. */

declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use App\Validators\InputValidator;

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

echo "Smoke test - InputValidator" . PHP_EOL;
echo str_repeat('─', 40) . PHP_EOL;

verificar('requireString', fn () =>
    InputValidator::requireString(['nombre' => 'Laura'], 'nombre')
);

verificar('requireEmail', fn () =>
    InputValidator::requireEmail(['email' => 'laura@example.com'], 'email')
);

verificar('requireDate', fn () =>
    InputValidator::requireDate(['fecha' => '2026-11-10'], 'fecha')
);

verificar('requireDateTime', fn () =>
    InputValidator::requireDateTime(['fecha' => '2026-10-17 19:30:00'], 'fecha')
);

verificar('requirePositiveInt', fn () =>
    InputValidator::requirePositiveInt(['num' => '2'], 'num')
);

verificar('requireEstado', fn () =>
    InputValidator::requireEstado(['estado' => 'pendiente'], 'estado')
);

echo str_repeat('─', 40) . PHP_EOL;

if ($fallos > 0) {
    echo "FALLIDO: {$fallos} test(s) no pasaron." . PHP_EOL;
    exit(1);
}

echo "OK: todos los tests de humo pasaron." . PHP_EOL;
exit(0);   