<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\{Role, User};
use App\Repositories\Interfaces\{RoleRepositoryInterface, PermissionRepositoryInterface};
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException};

// ═══════════════════════════════════════════════════════════
// UpdateRoleAction
// ═══════════════════════════════════════════════════════════

class UpdateRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    /**
     * @throws CannotModifySystemRoleException
     */
    public function __invoke(Role $role, array $data, User $updatedBy): Role
    {
        // ─── REGLA DE NEGOCIO: Roles del sistema son intocables ──
        //
        // Los roles super_admin, admin, manager, cashier, etc.
        // que cargó el seeder tienen is_system = true.
        // Ningún admin de empresa puede modificarlos.
        // Solo el super_admin del sistema puede hacerlo.
        if ($role->isSystem() && ! $updatedBy->hasPermission('system:manage')) {
            throw new CannotModifySystemRoleException(
                "El rol '{$role->display_name}' es un rol del sistema y no puede modificarse."
            );
        }

        // No permitir cambiar el name de un rol
        // (el name es el identificador técnico usado en el código)
        $allowedFields = array_filter([
            'display_name' => $data['display_name'] ?? null,
            'description'  => $data['description']  ?? null,
            'is_default'   => $data['is_default']   ?? null,
            'is_active'    => $data['is_active']     ?? null,
        ], fn($v) => $v !== null);

        return $this->roleRepository->update($role, $allowedFields);
    }
}
