<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Products extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'company_id',
        'category_id',
        'sale_price',
        'cost_price',
        'min_price',
        'sku',
        'slug',
        'type',
        'description',
        'price_includes_tax',
        'tax_rate',
        'has_variants',
        'is_active',
        'images',
        'attributes'
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'          => 'boolean',
            'has_variants'       => 'boolean',
            'price_includes_tax' => 'boolean',
            'cost_price'         => 'decimal:2',
            'sale_price'         => 'decimal:2',
            'min_price'          => 'decimal:2',
            'tax_rate'           => 'decimal:2',
            'preparation_time'   => 'integer',
            'images'             => 'array',
            'attributes'         => 'array',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    // ═══════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════
    public function getPriceWithTaxAttribute(): float
    {
        if (!$this->tax_rate || $this->price_includes_tax) {
            return (float) $this->sale_price;
        }

        return round($this->sale_price * (1 + $this->tax_rate / 100), 2);
    }

    public function getProfitMarginAttribute(): ?float
    {
        if (!$this->cost_price || $this->cost_price <= 0) {
            return null;
        }

        return round(
            (($this->sale_price - $this->cost_price) / $this->sale_price) * 100,
            2
        );
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS DE ESTADO
    // ═══════════════════════════════════════════════════════

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    // ═══════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════

    public function scopeAvailable($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
    
}
