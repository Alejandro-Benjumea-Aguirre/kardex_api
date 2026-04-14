<?php

declare(strict_types=1);

namespace App\Actions\ProductVariants;

use App\Models\ProductVariant;
use App\Repositories\ProductVariants\ProductVariantRepository;

class DeactivateProductVariantAction
{
    public function __construct(
        private readonly ProductVariantRepository $repository,
    ) {}

    public function execute(ProductVariant $variant): bool
    {
        $totalVariants = ProductVariant::where('product_id', $variant->product_id)
                                       ->where('is_active', true)
                                       ->count();

        if ($totalVariants === 1) {
            throw new \RuntimeException(
                'No se puede eliminar la única variante activa del producto.'
            );
        }

        if ($variant->barcodes()->exists()) {
            throw new \RuntimeException(
                'No se puede eliminar una variante que tiene códigos de barras. Eliminá primero los códigos.'
            );
        }

        if ($variant->is_default) {
            $this->assignNewDefault($variant->product_id, $variant->id);
        }

        return $this->repository->delete($variant);
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