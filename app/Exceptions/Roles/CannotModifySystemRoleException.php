<?php

declare(strict_types=1);

namespace App\Exceptions\Roles;

use App\Exceptions\Users\UsersException;

class CannotModifySystemRoleException extends UsersException
{
    public function httpStatus(): int    { return 403; }
    public function errorCode(): string { return 'SYSTEM_ROLE_PROTECTED'; }
}
