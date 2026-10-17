<?php
/** Sirve archivos públicos y envía el resto de rutas a la aplicación. */

declare(strict_types=1);

$projectRoot = __DIR__;

$publicDir = $projectRoot . '/public';
$rutaSolicitada = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$rutaNormalizada = realpath($publicDir . $rutaSolicitada);

if (
  $rutaSolicitada !== '/'
  && $rutaNormalizada !== false
  && str_starts_with($rutaNormalizada, $publicDir . DIRECTORY_SEPARATOR)
  && is_file($rutaNormalizada)
) {
  return false;
}

require $publicDir . '/index.php';
