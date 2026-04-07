<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────
// EXCEPCIONES
// ─────────────────────────────────────────────────────────

namespace App\Exceptions\Users;

class UsersException extends \RuntimeException
{
    public function httpStatus(): int    { return 400; }
    public function errorCode(): string { return 'USERS_ERROR'; }
}
