<?php

declare(strict_types=1);

namespace App\Exceptions\Inventaries;

class InventaryNotFoundException extends InventaryException
{
    public function __construct(string $msg = 'Inventario no encontrado.')
    { parent::__construct($msg); }
    public function httpStatus(): int    { return 404; }
    public function errorCode(): string { return 'INVENTARY_NOT_FOUND'; }
}
