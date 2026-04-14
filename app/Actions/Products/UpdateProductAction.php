<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Data\Products\UpdateProductData;
use App\Models\Product;
use App\Repositories\Products\ProductRepository;
use Illuminate\Support\Str;

class UpdateProductAction
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function execute(Product $product, UpdateProductData $data): Product
    {
        $updateData = array_filter($data->toArray(), fn($value) => !is_null($value));

        if (isset($updateData['name']) && !isset($updateData['slug'])) {
            $updateData['slug'] = Str::slug($updateData['name']);
        }

        return $this->productRepository->update($product, $updateData);
    }
}