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
        // ─── CONCEPTO: Query con OR en Eloquent ──────────────
        //
        // Necesitamos: roles de la empresa + roles globales (si includeGlobal)
        // En SQL: WHERE company_id = ? OR company_id IS NULL
        //
        // En Eloquent, nunca hagas:
        //   ->where('company_id', $id)->orWhereNull('company_id')
        //   Porque en queries más complejas el OR sin agrupar
        //   puede romper la lógica. Siempre envolvé en where():
        return Role::with('permissions')
            ->active()
            ->when($includeGlobal,
                fn($q) => $q->where(fn($inner) =>
                    $inner->where('company_id', $companyId)
                          ->orWhereNull('company_id')
                ),
                fn($q) => $q->where('company_id', $companyId)
            )
            ->orderBy('is_system', 'desc') // roles del sistema primero
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
        // Antes de borrar, revocar a todos los usuarios que tienen este rol
        // para no dejar registros huérfanos en user_roles
        $role->users()->detach();
        $role->delete(); // SoftDelete — no borra físicamente
    }

    public function syncPermissions(Role $role, array $permissionIds, ?string $grantedBy = null): void
    {
        // ─── CONCEPTO: sync() vs attach() vs syncWithoutDetaching() ──
        //
        // attach($ids):
        //   Agrega IDs a la pivote. Si ya existe, duplica.
        //   Úsalo cuando sabés que no existe todavía.
        //
        // detach($ids):
        //   Elimina IDs de la pivote.
        //
        // sync($ids):
        //   Reemplaza TODOS los registros de la pivote por exactamente $ids.
        //   Si había [A, B, C] y sync([B, D]), queda [B, D].
        //   A y C se eliminan, D se agrega.
        //   Es la operación más útil para "guardar permisos del rol".
        //
        // syncWithoutDetaching($ids):
        //   Solo agrega los que faltan, no elimina los que sobran.
        //   Úsalo para "agregar permisos sin quitar los existentes".
        //
        // Para SyncPermissions del rol usamos sync() porque queremos
        // que el resultado sea EXACTAMENTE los IDs pasados.

        // Preparar los IDs con el pivot data (granted_by)
        $syncData = collect($permissionIds)->mapWithKeys(fn($id) => [
            $id => ['granted_by' => $grantedBy],
        ])->all();

        $role->permissions()->sync($syncData);

        // Invaliar caché de todos los usuarios del rol
        // porque sus permisos efectivos cambiaron
        $role->invalidateUsersPermissionsCache();
    }

    public function getPermissionIds(Role $role): array
    {
        return $role->permissions()->pluck('permissions.id')->all();
    }
}
