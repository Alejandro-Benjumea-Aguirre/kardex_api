<?php

declare(strict_types=1);

// ─────────────────────────────────────────────────────────
// EXCEPCIONES
// ─────────────────────────────────────────────────────────

namespace App\Exceptions\Products;

class ProductsException extends \RuntimeException
{
    public function httpStatus(): int    { return 400; }
    public function errorCode(): string { return 'PRODUCT_ERROR'; }
}
