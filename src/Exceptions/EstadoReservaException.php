<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;

/** La transición solicitada no es compatible con el estado actual. */
final class EstadoReservaException extends DomainException {}
