<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────
// EXCEPCIONES
// ─────────────────────────────────────────────────────────

namespace App\Exceptions\Categories;

class CategoryException extends \RuntimeException
{
    public function httpStatus(): int    { return 400; }
    public function errorCode(): string { return 'CATEGORY_ERROR'; }
}
