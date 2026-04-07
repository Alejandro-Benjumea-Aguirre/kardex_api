<?php declare(strict_types=1);

namespace App\Exceptions\Auth;

class TokenExpiredException extends AuthException
{
    public function __construct(string $message = 'El token ha expirado.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int { return 401; }
    public function errorCode(): string { return 'TOKEN_EXPIRED'; }
}
