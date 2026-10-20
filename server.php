<?php

declare(strict_types=1);

/**
 * Router del servidor de desarrollo.
 *
 * Sirve archivos existentes dentro de public y delega
 * las demás peticiones en el punto de entrada de la aplicación.
 */

$publicDir = realpath(__DIR__ . '/public');

if ($publicDir === false) {
    http_response_code(500);

    echo 'No se ha encontrado el directorio público.';

    exit;
}

$rutaSolicitada = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
);

if (!is_string($rutaSolicitada)) {
    http_response_code(400);

    echo 'Ruta de petición no válida.';

    exit;
}

// Normaliza también el archivo solicitado antes de comprobar su ubicación.
$rutaNormalizada = realpath($publicDir . $rutaSolicitada);

// El separador final evita aceptar carpetas con un prefijo parecido,
// por ejemplo "public-privado" en lugar de "public".
$prefijoPublico = $publicDir . DIRECTORY_SEPARATOR;

if (
    $rutaSolicitada !== '/'
    && $rutaNormalizada !== false
    && str_starts_with($rutaNormalizada, $prefijoPublico)
    && is_file($rutaNormalizada)
) {
    // Deja que el servidor de PHP atienda el archivo público.
    return false;
}

// Las páginas y endpoints los gestiona la aplicación.
require $publicDir . DIRECTORY_SEPARATOR . 'index.php';