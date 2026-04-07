<?php

declare(strict_types=1);

namespace App\Exceptions\Roles;

use App\Exceptions\Users\UsersException;

class RoleNotFoundException extends UsersException
{
    public function __construct(string $msg = 'Rol no encontrado.')
    { parent::__construct($msg); }
    public function httpStatus(): int    { return 404; }
    public function errorCode(): string { return 'ROLE_NOT_FOUND'; }
}
