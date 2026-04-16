<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'inventory';

    protected $fillable = [
        'branch_id',
        'product_variant_id',
        'quantity',
        'min_stock',
        'max_stock',
        'location',
        'avg_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity'  => 'decimal:3',
            'min_stock' => 'decimal:3',
            'max_stock' => 'decimal:3',
            'avg_cost'  => 'decimal:4',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // ═══════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════

    // Valor total del stock — cantidad × costo promedio
    public function getTotalValueAttribute(): float
    {
        return round((float) $this->quantity * (float) $this->avg_cost, 2);
    }

    // Verifica si el stock está por debajo del mínimo
    public function isBelowMinStockAttribute(): bool
    {
        return (float) $this->quantity <= (float) $this->min_stock;
    }

    // Verifica si el stock está por encima del máximo
    public function isAboveMaxStockAttribute(): bool
    {
        if (!$this->max_stock) return false;

        return (float) $this->quantity >= (float) $this->max_stock;
    }

    // Porcentaje de stock disponible respecto al máximo
    public function getStockPercentageAttribute(): ?float
    {
        if (!$this->max_stock || (float) $this->max_stock === 0.0) {
            return null;
        }

        return round(((float) $this->quantity / (float) $this->max_stock) * 100, 2);
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS DE NEGOCIO
    // ═══════════════════════════════════════════════════════

    // Actualiza el costo promedio ponderado
    // Fórmula: ((stock_actual × costo_actual) + (cantidad_nueva × costo_nuevo))
    //          / (stock_actual + cantidad_nueva)
    public function recalculateAvgCost(float $newQuantity, float $newCost): float
    {
        $currentQuantity = (float) $this->quantity;
        $currentAvgCost  = (float) $this->avg_cost;

        if ($currentQuantity + $newQuantity === 0.0) {
            return 0.0;
        }

        return round(
            (($currentQuantity * $currentAvgCost) + ($newQuantity * $newCost))
            / ($currentQuantity + $newQuantity),
            4
        );
    }

    // ═══════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity', '<=', 'min_stock');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity', '<=', 0);
    }

    public function scopeOverStock($query)
    {
        return $query->whereNotNull('max_stock')
                     ->whereColumn('quantity', '>=', 'max_stock');
    }
}