<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Exceptions\EstadoReservaException;
use App\Services\ReservaEstadoService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ReservaEstadoServiceTest extends TestCase
{
  /** Comprueba que una reserva pendiente puede pasar a confirmada. */
  public function testPermiteConfirmarUnaPendiente(): void
  {
    self::assertTrue(
      ReservaEstadoService::requiereCambio(
        'pendiente',
        'confirmada'
      )
    );
  }

  /** Comprueba que una reserva pendiente puede pasar a cancelada. */
  public function testPermiteCancelarUnaPendiente(): void
  {
    self::assertTrue(
      ReservaEstadoService::requiereCambio(
        'pendiente',
        'cancelada'
      )
    );
  }

  /** Comprueba que repetir la confirmación no requiere otro cambio. */
  public function testNoRepiteUnaConfirmacion(): void
  {
    self::assertFalse(
      ReservaEstadoService::requiereCambio(
        'confirmada',
        'confirmada'
      )
    );
  }

  /** Comprueba que repetir la cancelación no requiere otro cambio. */
  public function testNoRepiteUnaCancelacion(): void
  {
    self::assertFalse(
      ReservaEstadoService::requiereCambio(
        'cancelada',
        'cancelada'
      )
    );
  }

  /** Comprueba que no se puede confirmar una reserva ya cancelada. */
  public function testRechazaConfirmarUnaCancelada(): void
  {
    $this->expectException(EstadoReservaException::class);

    ReservaEstadoService::requiereCambio(
      'cancelada',
      'confirmada'
    );
  }

  /** Comprueba que no se puede cancelar una reserva ya confirmada. */
  public function testRechazaCancelarUnaConfirmada(): void
  {
    $this->expectException(EstadoReservaException::class);

    ReservaEstadoService::requiereCambio(
      'confirmada',
      'cancelada'
    );
  }

  /** Comprueba que una reserva no puede volver al estado pendiente. */
  public function testRechazaVolverAPendiente(): void
  {
    $this->expectException(InvalidArgumentException::class);

    ReservaEstadoService::requiereCambio(
      'confirmada',
      'pendiente'
    );
  }

  /** Comprueba que se rechazan estados que no reconoce la aplicación. */
  public function testRechazaUnEstadoDesconocido(): void
  {
    $this->expectException(InvalidArgumentException::class);

    ReservaEstadoService::requiereCambio(
      'pendiente',
      'borrada'
    );
  }
}
