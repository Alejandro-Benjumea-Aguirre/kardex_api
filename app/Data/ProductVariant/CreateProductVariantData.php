<?php

declare(strict_types=1);

namespace App\Data\ProductVariants;

use Spatie\LaravelData\Data;

class CreateProductVariantData extends Data
{
    public function __construct(
        public readonly string  $product_id,
        public readonly string  $name,
        public readonly ?string $sku        = null,
        public readonly ?float  $cost_price = null,
        public readonly ?float  $sale_price = null,
        public readonly array   $attributes = [],
        public readonly ?string $image_url  = null,
        public readonly int     $sort_order = 0,
        public readonly bool    $is_active  = true,
        public readonly bool    $is_default = false,
    ) {}
}