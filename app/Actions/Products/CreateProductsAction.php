// app/Actions/Products/CreateProductAction.php
<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Data\Products\CreateProductData;
use App\Models\Product;
use App\Repositories\Interfaces\ProductRepository;
use Illuminate\Support\Str;

class CreateProductAction
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {}

    public function execute(CreateProductData $data): Product
    {
        $slug = $data->slug ?? Str::slug($data->name);
        $sku = $data->sku ?? $this->generateSku($data);
        $product = $this->productRepository->create([
            ...$data->toArray(),
            'slug' => $slug,
            'sku'  => $sku,
        ]);

        return $product;
    }

    private function generateSku(CreateProductData $data): string
    {
        $prefix  = strtoupper(substr($data->name, 0, 3));
        $counter = str_pad(
            string: (string) (Product::count() + 1),
            length: 3,
            pad_string: '0',
            pad_type: STR_PAD_LEFT
        );

        return "{$prefix}-{$counter}"; // Ej: BAN-001
    }
}