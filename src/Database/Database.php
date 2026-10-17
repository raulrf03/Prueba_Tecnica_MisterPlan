<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

class Database
{
  private static ?PDO $connection = null;

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

    self::$connection = new PDO($dsn, $database['user'], $database['password'], [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return self::$connection;
  }
}
