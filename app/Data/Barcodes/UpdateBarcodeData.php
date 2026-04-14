<?php

declare(strict_types=1);

namespace App\Data\Barcodes;

use Spatie\LaravelData\Data;

class UpdateBarcodeData extends Data
{
    public function __construct(
        public readonly ?string $code       = null,
        public readonly ?string $type       = null,
        public readonly ?bool   $is_primary = null,
    ) {}
}