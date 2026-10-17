<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;

$pdo = Database::getConnection();

$resultado = $pdo->query(
  'SELECT DATABASE() AS base_datos, CURRENT_USER() AS usuario'
)->fetch();

echo 'Conexión correcta' . PHP_EOL;
echo 'Base de datos: ' . $resultado['base_datos'] . PHP_EOL;
echo 'Usuario: ' . $resultado['usuario'] . PHP_EOL;


/*
salida obtenida y deseada 
Conexión correcta
Base de datos: reservas
Usuario: reservas_app@localhost 
*/