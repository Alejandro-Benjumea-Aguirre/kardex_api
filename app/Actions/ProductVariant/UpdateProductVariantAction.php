<?php

declare(strict_types=1);

namespace App\Actions\ProductVariants;

use App\Data\ProductVariants\UpdateProductVariantData;
use App\Models\ProductVariant;
use App\Repositories\ProductVariants\ProductVariantRepository;

class UpdateProductVariantAction
{
    public function __construct(
        private readonly ProductVariantRepository $repository,
    ) {}

    public function execute(ProductVariant $variant, UpdateProductVariantData $data): ProductVariant
    {
        $updateData = array_filter(
            $data->toArray(),
            fn($value) => !is_null($value)
        );

        if (isset($updateData['is_default']) && $updateData['is_default'] === true) {
            $this->clearDefaultVariants(
                productId:  $variant->product_id,
                excludeId:  $variant->id,
            );
        }

        return $this->repository->update($variant, $updateData);
    }

    // ── Helpers privados ─────────────────────────────────

    private function clearDefaultVariants(string $productId, string $excludeId): void
    {
        ProductVariant::where('product_id', $productId)
                      ->where('id', '!=', $excludeId)
                      ->where('is_default', true)
                      ->update(['is_default' => false]);
    }
}