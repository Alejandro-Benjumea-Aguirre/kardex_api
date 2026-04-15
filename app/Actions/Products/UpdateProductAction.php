<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Data\Products\UpdateProductsData;
use App\Models\Products;
use App\Repositories\Interfaces\ProductsRepositoryExtendedInterface;
use Illuminate\Support\Str;

class UpdateProductAction
{
    public function __construct(
        private readonly ProductsRepositoryExtendedInterface $productsRepository,
    ) {}

    public function __invoke(Products $product, UpdateProductsData $data): Products
    {
        $fields = array_filter($data->toArray(), fn($v) => $v !== null);

        if (isset($fields['name']) && !isset($fields['slug'])) {
            $fields['slug'] = Str::slug($fields['name']);
        }

        return $this->productsRepository->update($product, $fields);
    }
}
