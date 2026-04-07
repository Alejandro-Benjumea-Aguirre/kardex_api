<?php declare(strict_types=1);

namespace App\Exceptions\Auth;

class TooManyAttemptsException extends AuthException
{
    public function httpStatus(): int { return 429; }
    public function errorCode(): string { return 'TOO_MANY_ATTEMPTS'; }
}
