<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\{Role, User};
use App\Repositories\Interfaces\{RoleRepositoryInterface, PermissionRepositoryInterface};
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException};

// ═══════════════════════════════════════════════════════════
// SyncPermissionsAction
//
// CONCEPTO: Esta es la operación más importante del RBAC
// ═══════════════════════════════════════════════════════════
//
// Cuando el admin edita los permisos de un rol en la UI,
// manda un array completo de IDs de permisos.
// Esta Action reemplaza los permisos del rol por exactamente
// lo que viene en ese array.
//
// EJEMPLO:
//   Rol "Cajero" tiene: [product:read, sale:create]
//   Admin manda:        [product:read, sale:create, sale:void]
//   Resultado:          [product:read, sale:create, sale:void]
//
//   Admin manda:        [product:read]
//   Resultado:          [product:read]  ← sale:create fue removido
//
// IMPACTO EN CACHÉ:
// Cuando cambian los permisos de un rol, TODOS los usuarios
// que tienen ese rol necesitan que su caché se invalide.
// Eso lo hace roleRepository.syncPermissions() internamente.
// ═══════════════════════════════════════════════════════════

class SyncPermissionsAction
{
    public function __construct(
        private readonly RoleRepositoryInterface       $roleRepository,
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    public function __invoke(Role $role, array $permissionIds, User $updatedBy): Role
    {
        if ($role->isSystem() && ! $updatedBy->hasPermission('system:manage')) {
            throw new CannotModifySystemRoleException(
                "Los permisos del rol '{$role->display_name}' no pueden modificarse."
            );
        }

        // Validar que todos los IDs existan y pertenezcan al scope correcto
        // Esto evita que alguien asigne permisos de otra empresa
        $validPermissions = \App\Models\Permission::whereIn('id', $permissionIds)
            ->where(function ($q) use ($role) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $role->company_id);
            })
            ->pluck('id')
            ->all();

        // Solo sincronizamos los IDs que pasaron la validación
        $this->roleRepository->syncPermissions(
            role:          $role,
            permissionIds: $validPermissions,
            grantedBy:     $updatedBy->id,
        );

        return $role->fresh('permissions');
    }
}
