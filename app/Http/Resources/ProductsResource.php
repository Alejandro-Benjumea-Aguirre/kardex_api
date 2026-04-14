<?php

declare(strict_types=1);

namespace App\Http\Resources\Products;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // ── Identificación ──────────────────────────────
            'id'                 => $this->id,
            'name'               => $this->name,
            'slug'               => $this->slug,
            'sku'                => $this->sku,
            'description'        => $this->description,
            'type'               => $this->type,

            // ── Relaciones ───────────────────────────────────
            'company'            => $this->whenLoaded('company', fn() => [
                'id'   => $this->company->id,
                'name' => $this->company->name,
            ]),
            'category'           => $this->whenLoaded('category', fn() => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),

            // ── Precios ─────────────────────────────────────
            'prices'             => [
                'cost'              => $this->cost_price,
                'sale'              => $this->sale_price,
                'min'               => $this->min_price,
                'includes_tax'      => $this->price_includes_tax,
                'tax_rate'          => $this->tax_rate,
                // Precio con IVA calculado
                'sale_with_tax'     => $this->when(
                    $this->tax_rate && !$this->price_includes_tax,
                    fn() => round($this->sale_price * (1 + $this->tax_rate / 100), 2)
                ),
                // Margen de ganancia
                'margin'            => $this->when(
                    $this->cost_price > 0,
                    fn() => round((($this->sale_price - $this->cost_price) / $this->sale_price) * 100, 2)
                ),
            ],

            // ── Estado ───────────────────────────────────────
            'has_variants'       => $this->has_variants,

            // ── Media ────────────────────────────────────────
            'images'             => $this->whenLoaded('images', fn() =>
                $this->images->map(fn($image) => [
                    'id'  => $image->id,
                    'url' => $image->url,
                ])
            ),
            'attributes'         => $this->attributes ?? [],

            // ── Timestamps ───────────────────────────────────
            'timestamps'         => [
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
                'deleted_at' => $this->when(
                    $this->deleted_at,
                    fn() => $this->deleted_at?->format('Y-m-d H:i:s')
                ),
            ],
        ];
    }
}