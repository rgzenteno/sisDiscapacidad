<?php

namespace App\Exceptions;

use RuntimeException;

class PagoSuspendidoException extends RuntimeException
{
    public function __construct(string $message, public readonly string $campo = 'fecha_inicio')
    {
        parent::__construct($message);
    }
}
