<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Data\Users\AssignRoleData;
use App\Models\User;
use App\Repositories\Interfaces\{UserRepositoryExtendedInterface, RoleRepositoryInterface};
use App\Exceptions\Roles\{RoleNotFoundException, UserAlreadyHasRoleException};

// ═══════════════════════════════════════════════════════════
// AssignRoleAction
// ═══════════════════════════════════════════════════════════

class AssignRoleAction
{
    public function __construct(
        private readonly UserRepositoryExtendedInterface $userRepository,
        private readonly RoleRepositoryInterface         $roleRepository,
    ) {}

    /**
     * @throws RoleNotFoundException
     * @throws UserAlreadyHasRoleException
     */
    public function __invoke(User $user, AssignRoleData $data, User $assignedBy): void
    {
        $role = $this->roleRepository->findById($data->role_id);

        if (! $role) {
            throw new RoleNotFoundException("El rol no existe.");
        }

        // Verificar que el rol pertenece a la misma empresa
        // o es un rol global
        if ($role->company_id && $role->company_id !== $user->company_id) {
            throw new \DomainException('El rol no pertenece a esta empresa.');
        }

        // Verificar que el usuario no tenga ya este rol en este scope
        $yaExiste = $user->roles()
            ->where('roles.id', $data->role_id)
            ->wherePivot('branch_id', $data->branch_id)
            ->exists();

        if ($yaExiste) {
            throw new UserAlreadyHasRoleException('El usuario ya tiene este rol en el scope indicado.');
        }

        $this->userRepository->assignRole($user, $role, $data->branch_id, $assignedBy->id);
    }
}
