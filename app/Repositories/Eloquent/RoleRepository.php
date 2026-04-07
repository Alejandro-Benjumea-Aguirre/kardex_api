<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\{Role, Permission, User};
use App\Repositories\Interfaces\{RoleRepositoryInterface, PermissionRepositoryInterface};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

// ═══════════════════════════════════════════════════════════
// RoleRepository
// ═══════════════════════════════════════════════════════════

class RoleRepository implements RoleRepositoryInterface
{
    public function findById(string $id): ?Role
    {
        return Role::with('permissions')->find($id);
    }

    public function findByName(string $name, ?string $companyId = null): ?Role
    {
        return Role::where('name', $name)
                   ->where('company_id', $companyId)
                   ->first();
    }

    public function allForCompany(string $companyId, bool $includeGlobal = true): Collection
    {

        return Role::with('permissions')
            ->active()
            ->when($includeGlobal,
                fn($q) => $q->where(fn($inner) =>
                    $inner->where('company_id', $companyId)
                          ->orWhereNull('company_id')
                ),
                fn($q) => $q->where('company_id', $companyId)
            )
            ->orderBy('is_system', 'desc')
            ->orderBy('display_name')
            ->get();
    }

    public function create(array $data): Role
    {
        return Role::create($data);
    }

    public function update(Role $role, array $data): Role
    {
        $role->update($data);
        return $role->fresh('permissions');
    }

    public function delete(Role $role): void
    {
        $role->users()->detach();
        $role->delete();
    }

    public function syncPermissions(Role $role, array $permissionIds, ?string $grantedBy = null): void
    {

        $syncData = collect($permissionIds)->mapWithKeys(fn($id) => [
            $id => ['granted_by' => $grantedBy],
        ])->all();

        $role->permissions()->sync($syncData);

        $role->invalidateUsersPermissionsCache();
    }

    public function getPermissionIds(Role $role): array
    {
        return $role->permissions()->pluck('permissions.id')->all();
    }
}
