<?php

declare(strict_types=1);

namespace App\Exceptions\Users;

class UserNotFoundException extends UsersException
{
    public function __construct(string $msg = 'Usuario no encontrado.')
    { parent::__construct($msg); }
    public function httpStatus(): int    { return 404; }
    public function errorCode(): string { return 'USER_NOT_FOUND'; }
}
