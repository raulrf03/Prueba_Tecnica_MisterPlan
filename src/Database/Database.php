<?php

/** Gestiona la conexión compartida con la base de datos. */

declare(strict_types=1);

namespace App\Database;

use PDO;
use RuntimeException;

class Database
{
  private static ?PDO $connection = null;

  /** Devuelve la conexión PDO, creándola si hace falta. */
  public static function getConnection(): PDO
  {
    if (self::$connection instanceof PDO) {
      return self::$connection;
    }

    $config = require __DIR__ . '/../../config/config.php';
    $database = $config['database'];

    $dsn = sprintf(
      'mysql:host=%s;port=%d;dbname=%s;charset=%s',
      $database['host'],
      $database['port'],
      $database['name'],
      $database['charset']
    );

    $options = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $sslCa = $database['ssl_ca'] ?? null;

    if ($sslCa !== null) {
      if (!is_file($sslCa) || !is_readable($sslCa)) {
        throw new RuntimeException(
          'El certificado CA de MySQL no existe o no se puede leer.'
        );
      }

      $options[\Pdo\Mysql::ATTR_SSL_CA] = $sslCa;
      $options[\Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT] = true;
    }

    self::$connection = new PDO(
      $dsn,
      $database['user'],
      $database['password'],
      $options
    );

    return self::$connection;
  }
}
