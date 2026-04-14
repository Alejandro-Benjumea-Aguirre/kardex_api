<?php

declare(strict_types=1);

namespace App\Actions\ProductVariants;

use App\Models\ProductVariant;
use App\Repositories\ProductVariants\ProductVariantRepository;

class ActivateProductVariantAction
{
    public function __construct(
        private readonly ProductVariantRepository $repository,
    ) {}

    public function execute(ProductVariant $variant): ProductVariant
    {
        $newStatus = !$variant->is_active;

        if (!$newStatus) {
            $totalActive = ProductVariant::where('product_id', $variant->product_id)
                                         ->where('is_active', true)
                                         ->count();

            if ($totalActive === 1) {
                throw new \RuntimeException(
                    'No se puede desactivar la única variante activa del producto.'
                );
            }

            if ($variant->is_default) {
                $this->assignNewDefault($variant->product_id, $variant->id);
                $this->repository->update($variant, ['is_default' => false]);
            }
        }

        return $this->repository->update($variant, ['is_active' => $newStatus]);
    }

    // ── Helpers privados ─────────────────────────────────

    private function assignNewDefault(string $productId, string $excludeId): void
    {
        $newDefault = ProductVariant::where('product_id', $productId)
                                    ->where('id', '!=', $excludeId)
                                    ->where('is_active', true)
                                    ->orderBy('sort_order')
                                    ->first();

        if ($newDefault) {
            $newDefault->update(['is_default' => true]);
        }
    }
}