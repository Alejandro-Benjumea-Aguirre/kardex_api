<?php declare(strict_types=1);

namespace App\Exceptions\Auth;

class InvalidCredentialsException extends AuthException
{
    public function __construct(string $message = 'Credenciales incorrectas.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int { return 401; }
    public function errorCode(): string { return 'INVALID_CREDENTIALS'; }
}
