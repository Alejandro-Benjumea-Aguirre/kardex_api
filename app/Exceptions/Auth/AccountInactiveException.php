<?php declare(strict_types=1);

namespace App\Exceptions\Auth;

class AccountInactiveException extends AuthException
{
    public function __construct(string $message = 'La cuenta está desactivada. Contactá al administrador.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int { return 403; }
    public function errorCode(): string { return 'ACCOUNT_INACTIVE'; }
}
