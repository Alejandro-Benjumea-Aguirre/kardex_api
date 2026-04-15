<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\Products;
use App\Repositories\Interfaces\ProductsRepositoryExtendedInterface;

class DeactivateProductsAction
{
    public function __construct(
        private readonly ProductsRepositoryExtendedInterface $productsRepository,
    ) {}

    public function __invoke(Products $product): void
    {
        $this->productsRepository->deactivate($product);
    }
}
