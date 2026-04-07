<?php

declare(strict_types=1);

namespace App\Actions\Roles;

use App\Models\{Role, User};
use App\Repositories\Interfaces\{RoleRepositoryInterface, PermissionRepositoryInterface};
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException};

// ═══════════════════════════════════════════════════════════
// DeleteRoleAction
// ═══════════════════════════════════════════════════════════

class DeleteRoleAction
{
    public function __construct(
        private readonly RoleRepositoryInterface         $roleRepository,
        private readonly \App\Repositories\Interfaces\UserRepositoryExtendedInterface $userRepository,
    ) {}

    public function __invoke(Role $role, User $deletedBy): void
    {
        if ($role->isSystem()) {
            throw new CannotModifySystemRoleException(
                "El rol '{$role->display_name}' es del sistema y no puede eliminarse."
            );
        }

        // Verificar que no haya usuarios activos con este rol
        $usersWithRole = $this->userRepository
            ->getUsersWithRole($role->id)
            ->total();

        if ($usersWithRole > 0) {
            throw new \DomainException(
                "No podés eliminar el rol '{$role->display_name}' porque hay {$usersWithRole} usuario(s) asignado(s)."
            );
        }

        $this->roleRepository->delete($role);
    }
}
