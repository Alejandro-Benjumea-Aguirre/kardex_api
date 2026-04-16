<?php

declare(strict_types=1);

namespace App\Data\Category;

use Spatie\LaravelData\Data;

class CreateInventaryData extends Data
{
    public function __construct(
        public readonly int $branch_id,
        public readonly int  $product_variant_id,
        public readonly decimal  $quantity,
        public readonly decimal  $min_stock,
        public readonly decimal $max_stock,
        public readonly int     $location,
        public readonly decimal $avg_cost,
    ) {}
}
