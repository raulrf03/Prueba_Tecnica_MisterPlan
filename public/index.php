<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Database\Database;

header('Content-Type: text/html; charset=utf-8');

try {
  $pdo = Database::getConnection();

  $resultado = $pdo->query(
    'SELECT COUNT(*) AS total FROM reserva'
  )->fetch();

  $totalReservas = (int) $resultado['total'];
} catch (Throwable $e) {
  http_response_code(500);

  echo '<h1>Error de conexión</h1>';
  echo '<p>No se ha podido conectar con la base de datos.</p>';

  exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reservas de alojamiento</title>
</head>

<body>
  <h1>Reservas de alojamiento</h1>

  <p>
    La aplicación está conectada correctamente a la base de datos.
    Actualmente hay <?= htmlspecialchars((string) $totalReservas, ENT_QUOTES, 'UTF-8') ?> reservas registradas.
  </p>
</body>

</html>