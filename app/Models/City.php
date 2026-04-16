<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'cities';

    protected $fillable = [
        'country_id',
        'name',
        'dane_code',
        'department',
        'department_code',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'latitude'   => 'decimal:8',
            'longitude'  => 'decimal:8',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    // ═══════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDepartment($query, string $department)
    {
        return $query->where('department', $department);
    }

    public function scopeByDepartmentCode($query, string $code)
    {
        return $query->where('department_code', $code);
    }

    public function scopeByDaneCode($query, string $daneCode)
    {
        return $query->where('dane_code', $daneCode);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name',       'ilike', "%{$term}%")
              ->orWhere('department','ilike', "%{$term}%")
              ->orWhere('dane_code', 'ilike', "%{$term}%");
        });
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS
    // ═══════════════════════════════════════════════════════

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    // Retorna el nombre completo con departamento
    public function getFullNameAttribute(): string
    {
        return "{$this->name}, {$this->department}";
    }

    // Retorna las coordenadas como array
    public function getCoordinatesAttribute(): ?array
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }

        return [
            'latitude'  => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
        ];
    }
}