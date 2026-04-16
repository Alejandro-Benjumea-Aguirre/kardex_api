<?php

declare(strict_types=1);

namespace App\Exceptions\Category;

class CategoryNotFoundException extends CategoryException
{
    public function __construct(string $msg = 'Categoria no encontrada.')
    { parent::__construct($msg); }
    public function httpStatus(): int    { return 404; }
    public function errorCode(): string { return 'CATEGORY_NOT_FOUND'; }
}
