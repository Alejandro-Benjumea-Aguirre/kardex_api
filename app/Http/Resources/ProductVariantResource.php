// app/Http/Resources/ProductVariants/ProductVariantResource.php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Http\Resources\BarcodeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'sku'        => $this->sku,
            'attributes' => $this->attributes ?? [],
            'image_url'  => $this->image_url,
            'sort_order' => $this->sort_order,
            'is_active'  => $this->is_active,
            'is_default' => $this->is_default,

            // Precios — si es null usa el del producto base
            'prices' => [
                'cost'           => $this->cost_price,
                'sale'           => $this->sale_price,
                'effective_cost' => $this->effective_cost_price,
                'effective_sale' => $this->effective_sale_price,
            ],

            // Relaciones
            'product'  => $this->whenLoaded('product', fn() => [
                'id'   => $this->product->id,
                'name' => $this->product->name,
            ]),
            'barcodes' => BarcodeResource::collection(
                $this->whenLoaded('barcodes')
            ),

            'timestamps' => [
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            ],
        ];
    }
}