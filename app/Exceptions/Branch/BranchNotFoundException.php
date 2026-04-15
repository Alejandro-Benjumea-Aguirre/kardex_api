<?php

declare(strict_types=1);

namespace App\Exceptions\Branch;

class BranchNotFoundException extends BranchException
{
    public function __construct(string $msg = 'Sucursal no encontrada.')
    { parent::__construct($msg); }
    public function httpStatus(): int    { return 404; }
    public function errorCode(): string { return 'BRANCH_NOT_FOUND'; }
}
