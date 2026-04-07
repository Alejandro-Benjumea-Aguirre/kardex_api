<?php declare(strict_types=1);

namespace App\Exceptions\Auth;

class TokenInvalidException extends AuthException
{
    public function __construct(string $message = 'Token inválido.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int { return 401; }
    public function errorCode(): string { return 'TOKEN_INVALID'; }
}
