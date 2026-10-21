<?php

declare(strict_types=1);

namespace App\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

final class TwigFactory
{
  public static function crear(): Environment
  {
    $loader = new FilesystemLoader(__DIR__ . '/../../templates');

    return new Environment($loader, [
      'cache' => false,
      'autoescape' => 'html',
    ]);
  }
}
