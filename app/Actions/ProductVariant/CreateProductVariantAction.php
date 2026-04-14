<?php

declare(strict_types=1);

namespace App\Actions\ProductVariants;

use App\Data\ProductVariants\CreateProductVariantData;
use App\Models\ProductVariant;
use App\Repositories\ProductVariants\ProductVariantRepository;
use Illuminate\Support\Str;

class CreateProductVariantAction
{
    public function __construct(
        private readonly ProductVariantRepository $repository,
    ) {}

    public function execute(CreateProductVariantData $data): ProductVariant
    {
        $sku = $data->sku ?? $this->generateSku($data->name);

        if ($data->is_default) {
            $this->clearDefaultVariants($data->product_id);
        }

        return $this->repository->create([
            'product_id' => $data->product_id,
            'name'       => $data->name,
            'sku'        => $sku,
            'cost_price' => $data->cost_price,
            'sale_price' => $data->sale_price,
            'attributes' => $data->attributes,
            'image_url'  => $data->image_url,
            'sort_order' => $data->sort_order,
            'is_active'  => $data->is_active,
            'is_default' => $data->is_default,
        ]);
    }

    // ── Helpers privados ─────────────────────────────────

    private function generateSku(string $name): string
    {
        $prefix  = strtoupper(substr(Str::slug($name, ''), 0, 3));
        $counter = str_pad(
            string:     (string) (ProductVariant::count() + 1),
            length:     3,
            pad_string: '0',
            pad_type:   STR_PAD_LEFT
        );

        return "{$prefix}-{$counter}"; // Ej: COC-001, TAL-002
    }

    private function clearDefaultVariants(string $productId): void
    {
        ProductVariant::where('product_id', $productId)
                      ->where('is_default', true)
                      ->update(['is_default' => false]);
    }
}