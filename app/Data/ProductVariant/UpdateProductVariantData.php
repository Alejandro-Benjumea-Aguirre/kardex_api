<?php

declare(strict_types=1);

namespace App\Data\ProductVariants;

use Spatie\LaravelData\Data;

class UpdateProductVariantData extends Data
{
    public function __construct(
        public readonly ?string $name       = null,
        public readonly ?string $sku        = null,
        public readonly ?float  $cost_price = null,
        public readonly ?float  $sale_price = null,
        public readonly ?array  $attributes = null,
        public readonly ?string $image_url  = null,
        public readonly ?int    $sort_order = null,
        public readonly ?bool   $is_active  = null,
        public readonly ?bool   $is_default = null,
    ) {}
}