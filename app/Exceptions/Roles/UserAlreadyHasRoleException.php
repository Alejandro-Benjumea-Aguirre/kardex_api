<?php

declare(strict_types=1);

namespace App\Exceptions\Roles;

use App\Exceptions\Users\UsersException;

class UserAlreadyHasRoleException extends UsersException
{
    public function httpStatus(): int    { return 409; }
    public function errorCode(): string { return 'ROLE_ALREADY_ASSIGNED'; }
}
