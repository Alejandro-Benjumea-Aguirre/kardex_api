<?php declare(strict_types=1);

namespace App\Exceptions\Auth;

class EmailNotVerifiedException extends AuthException
{
    public function __construct(string $message = 'Debés verificar tu email antes de iniciar sesión.')
    {
        parent::__construct($message);
    }

    public function httpStatus(): int { return 403; }
    public function errorCode(): string { return 'EMAIL_NOT_VERIFIED'; }
}
