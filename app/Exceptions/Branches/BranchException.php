<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────
// EXCEPCIONES
// ─────────────────────────────────────────────────────────

namespace App\Exceptions\Branch;

class BranchException extends \RuntimeException
{
    public function httpStatus(): int    { return 400; }
    public function errorCode(): string { return 'BRANC_ERROR'; }
}
