// app/Models/ProductVariant.php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'product_variants';

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'cost_price',
        'sale_price',
        'attributes',
        'image_url',
        'sort_order',
        'is_active',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'attributes' => 'array',
            'cost_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'is_active'  => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════

    public function product(): BelongsTo
    {
        return $this->belongsTo(Products::class, 'product_id');
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(Barcode::class, 'product_variant_id');
    }

    public function primaryBarcode(): HasMany
    {
        return $this->hasMany(Barcode::class, 'product_variant_id')
                    ->where('is_primary', true);
    }

    // ═══════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS
    // ═══════════════════════════════════════════════════════

    // Si la variante no tiene precio propio, usa el del producto
    public function getEffectiveSalePriceAttribute(): float
    {
        return (float) ($this->sale_price ?? $this->product->sale_price);
    }

    public function getEffectiveCostPriceAttribute(): float
    {
        return (float) ($this->cost_price ?? $this->product->cost_price);
    }
}