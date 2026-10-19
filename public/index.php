<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\ReservaApiController;
use App\Routes\Router;
use App\Security\Csrf;

header('Content-Type: text/html; charset=utf-8');

$ruta = Router::obtenerRuta();

if (Router::esRutaApi($ruta)) {
    $controlador = new ReservaApiController();

    if ($ruta === '/api/reservas') {
        // POST crea una reserva; los GET con parámetros aplican filtros.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controlador->crear();

            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_GET !== []) {
            $controlador->buscar();

            exit;
        }

        // Un GET sin filtros devuelve todas las reservas.
        $controlador->listar();

        exit;
    }

    if (preg_match('#^/api/reservas/(\d+)$#', $ruta, $coincidencias) === 1) {
        $controlador->obtener((int) $coincidencias[1]);

        exit;
    }

    if (preg_match('#^/api/reservas/(\d+)/estado$#', $ruta, $coincidencias) === 1) {
        // El cambio de estado solo se permite mediante PATCH.
        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
            $controlador->cambiarEstado((int) $coincidencias[1]);

            exit;
        }

        Router::responderError('Método no permitido.', 405);

        exit;
    }

    Router::responderError('Endpoint no encontrado.', 404);

    exit;
}

// El token CSRF se incluye en la página para proteger las peticiones de escritura.
$csrfToken = Csrf::obtenerToken();
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas de alojamiento</title>
</head>
<body>
    <h1>Reservas de alojamiento</h1>

    <p>La aplicación está funcionando y preparada para separar vistas HTML y API.</p>
</body>
</html>