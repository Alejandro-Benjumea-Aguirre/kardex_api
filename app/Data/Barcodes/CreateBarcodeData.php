<?php

declare(strict_types=1);

namespace App\Data\Barcodes;

use Spatie\LaravelData\Data;

class CreateBarcodeData extends Data
{
    public function __construct(
        public readonly string $product_variant_id,
        public readonly string $code,
        public readonly string $type       = 'ean13',
        public readonly bool   $is_primary = false,
    ) {}
}