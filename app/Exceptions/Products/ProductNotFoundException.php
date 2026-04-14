<?php

declare(strict_types=1);

namespace App\Exceptions\Products;

class ProductNotFoundException extends ProductsException
{
    public function __construct(string $msg = 'Producto no encontrado.')
    { parent::__construct($msg); }
    public function httpStatus(): int    { return 404; }
    public function errorCode(): string { return 'PRODUCT_NOT_FOUND'; }
}
