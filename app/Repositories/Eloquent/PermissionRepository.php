<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\{Role, Permission, User};
use App\Repositories\Interfaces\{RoleRepositoryInterface, PermissionRepositoryInterface};
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

// ═══════════════════════════════════════════════════════════
// PermissionRepository
// ═══════════════════════════════════════════════════════════

class PermissionRepository implements PermissionRepositoryInterface
{
    public function allActive(?string $companyId = null): Collection
    {
        return Permission::active()
            ->when(
                $companyId,
                fn($q) => $q->forCompany($companyId),
                fn($q) => $q->whereNull('company_id') // solo globales si no hay company
            )
            ->orderBy('module')
            ->orderBy('sort_order')
            ->get();
    }

    // ─── CONCEPTO: groupBy() en colecciones Laravel ───────
    //
    // Al mostrar permisos en la UI de "editar rol",
    // necesitamos agruparlos por módulo:
    //
    //   products: [read, create, update, delete, import, export]
    //   sales:    [read, create, void, refund, discount]
    //   ...
    //
    // $collection->groupBy('module') devuelve una Collection
    // donde cada clave es el módulo y el valor es otra Collection
    // con los permisos de ese módulo.
    //
    // Luego ->toArray() la convierte en array PHP puro.
    // Esto es lo que va al Resource y finalmente al JSON.
    public function allGroupedByModule(?string $companyId = null): array
    {
        return $this->allActive($companyId)
            ->groupBy('module')
            ->map(fn($perms) => $perms->values())
            ->toArray();
    }

    public function findById(string $id): ?Permission
    {
        return Permission::find($id);
    }

    public function findByName(string $name): ?Permission
    {
        return Permission::where('name', $name)->first();
    }

    public function findByNames(array $names): Collection
    {
        return Permission::whereIn('name', $names)->get();
    }
}
