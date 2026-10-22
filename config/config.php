<?php

/** Carga las variables de entorno y devuelve la configuración de la base de datos. */

declare(strict_types=1);

\Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();

// Lee una variable de .env o del entorno del sistema.
$getEnv = static function (string $name): ?string {
  $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

  return $value === false || $value === '' ? null : (string) $value;
};

// Obtiene una variable necesaria para conectar con la base de datos.
$requiredEnv = static function (string $name) use ($getEnv): string {
  $value = $getEnv($name);

  if ($value === null) {
    throw new RuntimeException("Falta la variable de entorno {$name}.");
  }

  return $value;
};

return [
  'database' => [
    'host' => $getEnv('DB_HOST') ?? '127.0.0.1',
    'port' => (int) ($getEnv('DB_PORT') ?? '3306'),
    'name' => $requiredEnv('DB_NAME'),
    'user' => $requiredEnv('DB_USER'),
    'password' => $requiredEnv('DB_PASSWORD'),
    'charset' => 'utf8mb4',
  ],
];
