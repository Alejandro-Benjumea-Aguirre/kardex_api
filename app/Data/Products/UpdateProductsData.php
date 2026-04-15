// app/Data/Products/ProductData.php
<?php

namespace App\Data\Products;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Rule;

class UpdateProductsData extends Data
{
    public function __construct(
        public readonly ?string $name             = null,
        public readonly ?string $category_id      = null,
        public readonly ?float  $sale_price       = null,
        public readonly ?float  $cost_price       = null,
        public readonly ?string $sku              = null,
        public readonly ?string $slug             = null,
        public readonly ?string $description      = null,
        public readonly ?float  $min_price        = null,
        public readonly ?float  $tax_rate         = null,
        public readonly ?bool   $has_variants     = null,
    ) {}
}