<?php

declare(strict_types=1);

class InsufficientPermissionsException extends UsersException
{
    public function __construct(string $permission)
    { parent::__construct("No tenés el permiso requerido: {$permission}"); }
    public function httpStatus(): int    { return 403; }
    public function errorCode(): string { return 'INSUFFICIENT_PERMISSIONS'; }
}
