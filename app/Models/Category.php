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

class Category extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes;

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

    public function parent()
    {
        return $this->belongsTo(Categoria::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Categoria::class, 'parent_id');
    }

    public function childrenRecursivos()
    {
        return $this->hasMany(Categoria::class, 'parent_id')
                    ->with('childrenRecursivos');
    }

    // ═══════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════


    // ═══════════════════════════════════════════════════════
    // MÉTODOS DE ESTADO
    // ═══════════════════════════════════════════════════════

    public function isActive(): bool
    {
        return (bool) $this->is_active;
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
