<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\EstadoReservaException;
use InvalidArgumentException;

/** Define las transiciones permitidas sin depender de HTTP ni de la BD. */
final class ReservaEstadoService
{
  /** Valida cambios desde pendiente y devuelve false si el estado ya coincide. */
  public static function requiereCambio(
    string $estadoActual,
    string $nuevoEstado
  ): bool {
    if (!in_array($nuevoEstado, ['confirmada', 'cancelada'], true)) {
      throw new InvalidArgumentException(
        'El nuevo estado debe ser confirmada o cancelada.'
      );
    }

    if ($estadoActual === $nuevoEstado) {
      return false;
    }

    if ($estadoActual !== 'pendiente') {
      throw new EstadoReservaException(
        "No se puede cambiar una reserva {$estadoActual} "
          . "al estado {$nuevoEstado}."
      );
    }

    return true;
  }
}
