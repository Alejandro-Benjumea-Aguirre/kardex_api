// app/Http/Resources/Barcodes/BarcodeResource.php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BarcodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'code'       => $this->code,
            'type'       => $this->type,
            'is_primary' => $this->is_primary,

            // Solo se incluye si se cargó la relación
            'variant' => $this->whenLoaded('variant', fn() => [
                'id'   => $this->variant->id,
                'name' => $this->variant->name,
                'sku'  => $this->variant->sku,
            ]),

            'timestamps' => [
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ],
        ];
    }
}