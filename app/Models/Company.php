<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'companies';

    protected $fillable = [
        'name',
        'nit',
        'sector',
        'phone',
        'address',
        'city',
        'country',
        'website',
        'slug',
        'plan',
        'plan_limits',
        'settings',
        'logo_url',
        'is_active',
    ];

    protected $hidden = [
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'plan_limits' => 'array',
            'settings'    => 'array',
            'is_active'   => 'boolean',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // HOOKS
    // ═══════════════════════════════════════════════════════

    protected static function booted(): void
    {
        // Genera el slug automáticamente si no viene
        static::creating(function (Company $company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });

        // Regenera el slug si cambia el nombre
        static::updating(function (Company $company) {
            if ($company->isDirty('name') && !$company->isDirty('slug')) {
                $company->slug = Str::slug($company->name);
            }
        });
    }

    // ═══════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // ═══════════════════════════════════════════════════════
    // ACCESSORS — Settings
    // ═══════════════════════════════════════════════════════

    public function getCurrencyAttribute(): string
    {
        return $this->settings['currency'] ?? 'COP';
    }

    public function getTimezoneAttribute(): string
    {
        return $this->settings['timezone'] ?? 'America/Bogota';
    }

    public function getTaxRateAttribute(): int
    {
        return $this->settings['tax_rate'] ?? 19;
    }

    public function getInvoicePrefixAttribute(): string
    {
        return $this->settings['invoice_prefix'] ?? 'FAC';
    }

    public function getDateFormatAttribute(): string
    {
        return $this->settings['date_format'] ?? 'DD/MM/YYYY';
    }

    // ═══════════════════════════════════════════════════════
    // ACCESSORS — Plan limits
    // ═══════════════════════════════════════════════════════

    public function getMaxBranchesAttribute(): int
    {
        return $this->plan_limits['max_branches'] ?? 1;
    }

    public function getMaxUsersAttribute(): int
    {
        return $this->plan_limits['max_users'] ?? 3;
    }

    public function getMaxProductsAttribute(): int
    {
        return $this->plan_limits['max_products'] ?? 100;
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS DE ESTADO
    // ═══════════════════════════════════════════════════════

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isOnPlan(string $plan): bool
    {
        return $this->plan === $plan;
    }

    public function isFree(): bool
    {
        return $this->plan === 'free';
    }

    public function isEnterprise(): bool
    {
        return $this->plan === 'enterprise';
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS DE LÍMITES
    // ═══════════════════════════════════════════════════════

    public function hasReachedUserLimit(): bool
    {
        return $this->users()->count() >= $this->max_users;
    }

    public function hasReachedProductLimit(): bool
    {
        return $this->products()->count() >= $this->max_products;
    }

    // ═══════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPlan($query, string $plan)
    {
        return $query->where('plan', $plan);
    }
}
