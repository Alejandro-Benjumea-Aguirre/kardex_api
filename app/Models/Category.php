<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Category extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'image_url',
        'is_active',
        'parent_id',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function childrenRecursivos(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
                    ->with('childrenRecursivos');
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS DE ESTADO
    // ═══════════════════════════════════════════════════════

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}
