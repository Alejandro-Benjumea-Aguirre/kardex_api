<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_movements';

    const UPDATED_AT = null;

    protected $fillable = [
        'branch_id',
        'product_variant_id',
        'type',
        'quantity',
        'stock_before',
        'stock_after',
        'unit_cost',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity'     => 'decimal:3',
            'stock_before' => 'decimal:3',
            'stock_after'  => 'decimal:3',
            'unit_cost'    => 'decimal:4',
            'created_at'   => 'datetime',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relación polimórfica hacia el documento origen
    // Puede ser una venta, compra, transferencia, etc.
    public function reference(): MorphTo
    {
        return $this->morphTo('reference');
    }

    // ═══════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════

    // Indica si el movimiento suma o resta stock
    public function getIsEntryAttribute(): bool
    {
        return in_array($this->type, [
            'purchase',
            'sale_return',
            'adjustment_in',
            'transfer_in',
            'initial',
        ], true);
    }

    public function getIsExitAttribute(): bool
    {
        return in_array($this->type, [
            'sale',
            'purchase_return',
            'adjustment_out',
            'transfer_out',
            'waste',
        ], true);
    }

    // Valor total del movimiento
    public function getTotalCostAttribute(): ?float
    {
        if (!$this->unit_cost) return null;

        return round((float) $this->quantity * (float) $this->unit_cost, 2);
    }

    // Diferencia de stock
    public function getStockDifferenceAttribute(): float
    {
        return round((float) $this->stock_after - (float) $this->stock_before, 3);
    }

    // Label legible del tipo de movimiento
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'purchase'         => 'Compra a proveedor',
            'sale'             => 'Venta',
            'sale_return'      => 'Devolución de cliente',
            'purchase_return'  => 'Devolución a proveedor',
            'adjustment_in'    => 'Ajuste positivo',
            'adjustment_out'   => 'Ajuste negativo',
            'transfer_in'      => 'Transferencia recibida',
            'transfer_out'     => 'Transferencia enviada',
            'waste'            => 'Merma o daño',
            'initial'          => 'Stock inicial',
            default            => 'Desconocido',
        };
    }

    // ═══════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════

    public function scopeByBranch($query, string $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByVariant($query, string $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeEntries($query)
    {
        return $query->whereIn('type', [
            'purchase',
            'sale_return',
            'adjustment_in',
            'transfer_in',
            'initial',
        ]);
    }

    public function scopeExits($query)
    {
        return $query->whereIn('type', [
            'sale',
            'purchase_return',
            'adjustment_out',
            'transfer_out',
            'waste',
        ]);
    }

    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    public function scopeByReference($query, string $type, string $id)
    {
        return $query->where('reference_type', $type)
                     ->where('reference_id', $id);
    }
}