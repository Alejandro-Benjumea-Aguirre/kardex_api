<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'phone',
        'avatar_url',
        'is_active',
        'is_email_verified',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'is_email_verified' => 'boolean',
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELACIONES
    // ═══════════════════════════════════════════════════════

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_users')
                    ->withPivot(['is_default', 'assigned_at'])
                    ->withTimestamps();
    }

    // ═══════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getInitialsAttribute(): string
    {
        return strtoupper(
            substr($this->first_name, 0, 1) . substr($this->last_name, 0, 1)
        );
    }

    // ═══════════════════════════════════════════════════════
    // MÉTODOS DE ESTADO
    // ═══════════════════════════════════════════════════════

    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    public function isEmailVerified(): bool
    {
        return (bool) $this->is_email_verified;
    }

    // ═══════════════════════════════════════════════════════
    // PERMISOS (caché por sucursal)
    // ═══════════════════════════════════════════════════════

    public function getCachedPermissions(string $branchId): array
    {
        return cache()->get("permissions:{$this->id}:{$branchId}", []);
    }

    public function loadAndCachePermissions(string $branchId): array
    {
        $this->loadMissing('roles.permissions');

        $permissions = $this->roles
            ->flatMap(fn($role) => $role->permissions->pluck('name'))
            ->unique()
            ->values()
            ->all();

        cache()->put(
            "permissions:{$this->id}:{$branchId}",
            $permissions,
            now()->addMinutes(15)
        );

        $this->trackCachedBranch($branchId);

        return $permissions;
    }

    public function hasPermission(string $permission, ?string $branchId = null): bool
    {
        $cacheKey = $branchId ?? '';
        $cached   = $this->getCachedPermissions($cacheKey);

        if (empty($cached)) {
            $cached = $this->loadAndCachePermissions($cacheKey);
        }

        return in_array($permission, $cached, true);
    }

    public function hasAnyPermission(array $permissions, ?string $branchId = null): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission, $branchId)) {
                return true;
            }
        }

        return false;
    }

    public function invalidatePermissionsCache(): void
    {
        $listKey = "permissions_branches:{$this->id}";
        $branches = cache()->get($listKey, []);

        foreach ($branches as $branchId) {
            cache()->forget("permissions:{$this->id}:{$branchId}");
        }

        cache()->forget($listKey);
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ═══════════════════════════════════════════════════════

    private function trackCachedBranch(string $branchId): void
    {
        $listKey  = "permissions_branches:{$this->id}";
        $branches = cache()->get($listKey, []);

        if (! in_array($branchId, $branches, true)) {
            $branches[] = $branchId;
            cache()->put($listKey, $branches, now()->addDays(30));
        }
    }
}
