<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'countries';

    protected $fillable = [
        'name',
        'native_name',
        'iso2',
        'iso3',
        'phone_code',
        'capital',
        'currency',
        'currency_symbol',
        'region',
        'subregion',
        'flag',
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
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

    public function scopeByRegion($query, string $region)
    {
        return $query->where('region', $region);
    }

    public function scopeByIso2($query, string $iso2)
    {
        return $query->where('iso2', strtoupper($iso2));
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS
    // ═══════════════════════════════════════════════════════

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }
}