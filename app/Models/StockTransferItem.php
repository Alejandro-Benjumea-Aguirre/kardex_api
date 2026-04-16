<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_transfer_items';

    protected $fillable = [
        'transfer_id',
        'product_variant_id',
        'quantity_sent',
        'quantity_received',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_sent'     => 'decimal:3',
            'quantity_received' => 'decimal:3',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // ═══════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════

    // Diferencia entre lo enviado y lo recibido
    public function getQuantityDifferenceAttribute(): ?float
    {
        if ($this->quantity_received === null) {
            return null;
        }

        return round(
            (float) $this->quantity_sent - (float) $this->quantity_received,
            3
        );
    }

    // Indica si hubo diferencia entre lo enviado y recibido
    public function hasDifferenceAttribute(): bool
    {
        if ($this->quantity_received === null) {
            return false;
        }

        return (float) $this->quantity_sent !== (float) $this->quantity_received;
    }

    // Indica si el item ya fue recibido
    public function getIsReceivedAttribute(): bool
    {
        return $this->quantity_received !== null;
    }

    // Porcentaje recibido respecto a lo enviado
    public function getReceivedPercentageAttribute(): ?float
    {
        if ($this->quantity_received === null) {
            return null;
        }

        if ((float) $this->quantity_sent === 0.0) {
            return 0.0;
        }

        return round(
            ((float) $this->quantity_received / (float) $this->quantity_sent) * 100,
            2
        );
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS DE ESTADO
    // ═══════════════════════════════════════════════════════

    public function isPending(): bool
    {
        return $this->quantity_received === null;
    }

    public function isComplete(): bool
    {
        if ($this->quantity_received === null) {
            return false;
        }

        return (float) $this->quantity_sent === (float) $this->quantity_received;
    }

    public function isPartial(): bool
    {
        if ($this->quantity_received === null) {
            return false;
        }

        return (float) $this->quantity_received < (float) $this->quantity_sent;
    }

    public function hasExcess(): bool
    {
        if ($this->quantity_received === null) {
            return false;
        }

        return (float) $this->quantity_received > (float) $this->quantity_sent;
    }

    // ═══════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════

    public function scopePending($query)
    {
        return $query->whereNull('quantity_received');
    }

    public function scopeReceived($query)
    {
        return $query->whereNotNull('quantity_received');
    }

    public function scopeWithDifferences($query)
    {
        return $query->whereNotNull('quantity_received')
                     ->whereColumn('quantity_sent', '!=', 'quantity_received');
    }

    public function scopeByTransfer($query, string $transferId)
    {
        return $query->where('transfer_id', $transferId);
    }

    public function scopeByVariant($query, string $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }
}