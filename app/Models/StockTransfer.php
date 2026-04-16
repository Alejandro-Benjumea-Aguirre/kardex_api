<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'stock_transfers';

    protected $fillable = [
        'from_branch_id',
        'to_branch_id',
        'status',
        'notes',
        'created_by',
        'received_by',
        'sent_at',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at'     => 'datetime',
            'received_at' => 'datetime',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'from_branch_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'to_branch_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Items de la transferencia
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id');
    }

    // Movimientos de stock generados por esta transferencia
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'reference_id')
                    ->where('reference_type', 'transfer');
    }

    // ═══════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════

    // Label legible del estado
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending'    => 'Pendiente',
            'in_transit' => 'En tránsito',
            'received'   => 'Recibida',
            'cancelled'  => 'Cancelada',
            default      => 'Desconocido',
        };
    }

    // Tiempo que tardó la transferencia en completarse
    public function getTransitTimeAttribute(): ?string
    {
        if (!$this->sent_at || !$this->received_at) {
            return null;
        }

        $minutes = $this->sent_at->diffInMinutes($this->received_at);

        if ($minutes < 60) {
            return "{$minutes} min";
        }

        $hours = $this->sent_at->diffInHours($this->received_at);

        if ($hours < 24) {
            return "{$hours} horas";
        }

        $days = $this->sent_at->diffInDays($this->received_at);

        return "{$days} días";
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS DE ESTADO
    // ═══════════════════════════════════════════════════════

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeSent(): bool
    {
        return $this->isPending();
    }

    public function canBeReceived(): bool
    {
        return $this->isInTransit();
    }

    public function canBeCancelled(): bool
    {
        return $this->isPending() || $this->isInTransit();
    }

    // ═══════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', 'in_transit');
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeFromBranch($query, string $branchId)
    {
        return $query->where('from_branch_id', $branchId);
    }

    public function scopeToBranch($query, string $branchId)
    {
        return $query->where('to_branch_id', $branchId);
    }

    // Transferencias que involucran una sucursal (origen o destino)
    public function scopeInvolvingBranch($query, string $branchId)
    {
        return $query->where(function ($q) use ($branchId) {
            $q->where('from_branch_id', $branchId)
              ->orWhere('to_branch_id', $branchId);
        });
    }

    public function scopeBetweenDates($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }
}