<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Controllers\ReservaApiController;
use App\Controllers\ReservaViewController;
use App\Routes\Router;

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

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      if ($_GET !== []) {
        $controlador->buscar();

        exit;
      }

      $controlador->listar();

      exit;
    }

    header('Allow: GET, POST');
    Router::responderError('Método no permitido.', 405);

    exit;
  }

  if (preg_match('#^/api/reservas/(\d+)$#', $ruta, $coincidencias) === 1) {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $controlador->obtener((int) $coincidencias[1]);

      exit;
    }

    header('Allow: GET');
    Router::responderError('Método no permitido.', 405);

    exit;
  }

  if (preg_match('#^/api/reservas/(\d+)/estado$#', $ruta, $coincidencias) === 1) {
    // El cambio de estado solo se permite mediante PATCH.
    if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
      $controlador->cambiarEstado((int) $coincidencias[1]);

      exit;
    }

    header('Allow: PATCH');
    Router::responderError('Método no permitido.', 405);

    exit;
  }

  Router::responderError('Endpoint no encontrado.', 404);

  exit;
}


// Rutas HTML renderizadas mediante Twig.
$controladorVistas = new ReservaViewController();
$metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Página principal: listado de reservas.
if ($ruta === '/') {
  if ($metodo !== 'GET') {
    header('Allow: GET');
    http_response_code(405);

    echo 'Método no permitido.';

    exit;
  }

  $controladorVistas->listar();

  exit;
}

// Página de detalle de una reserva.
if (preg_match('#^/reservas/(\d+)$#', $ruta, $coincidencias) === 1) {
  if ($metodo !== 'GET') {
    header('Allow: GET');
    http_response_code(405);

    echo 'Método no permitido.';

    exit;
  }

  $controladorVistas->detalle((int) $coincidencias[1]);

  exit;
}

// Las rutas HTML no registradas usan la plantilla de error.
$controladorVistas->noEncontrado();

exit;
