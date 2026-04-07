<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\{Role, User};
use App\Repositories\Interfaces\{RoleRepositoryInterface, PermissionRepositoryInterface};
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException};

// ═══════════════════════════════════════════════════════════
// CreateRoleAction
// ═══════════════════════════════════════════════════════════

class CreateRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    public function __invoke(array $data, User $createdBy): Role
    {
        // Los roles custom siempre pertenecen a la empresa del creador
        // Nunca pueden ser globales (company_id = NULL) — eso solo
        // lo hace el seeder del sistema
        $role = $this->roleRepository->create([
            'company_id'   => $createdBy->company_id,
            'name'         => \Str::slug($data['display_name'], '_'), // "Mi Rol" → "mi_rol"
            'display_name' => $data['display_name'],
            'description'  => $data['description'] ?? null,
            'is_default'   => $data['is_default']  ?? false,
            'is_system'    => false,   // Los roles creados por empresas NUNCA son del sistema
            'is_active'    => true,
        ]);

        // Si vienen permisos, asignarlos de una vez
        if (! empty($data['permission_ids'])) {
            $this->roleRepository->syncPermissions(
                role:         $role,
                permissionIds: $data['permission_ids'],
                grantedBy:    $createdBy->id,
            );
        }

        return $role->fresh('permissions');
    }
}
