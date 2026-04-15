<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Data\Products\CreateProductsData;
use App\Models\Products;
use App\Repositories\Interfaces\ProductsRepositoryExtendedInterface;
use Illuminate\Support\Str;

class CreateProductsAction
{
    public function __construct(
        private readonly ProductsRepositoryExtendedInterface $productsRepository,
    ) {}

    public function __invoke(CreateProductsData $data): Products
    {
        $slug = $data->slug ?? Str::slug($data->name);
        $sku  = $data->sku  ?? $this->generateSku($data->name);

        return $this->productsRepository->create([
            ...$data->toArray(),
            'slug' => $slug,
            'sku'  => $sku,
        ]);
    }

    private function generateSku(string $name): string
    {
        $prefix  = strtoupper(substr($name, 0, 3));
        $counter = str_pad(
            string:     (string) (Products::count() + 1),
            length:     3,
            pad_string: '0',
            pad_type:   STR_PAD_LEFT
        );

        return "{$prefix}-{$counter}";
    }
}
