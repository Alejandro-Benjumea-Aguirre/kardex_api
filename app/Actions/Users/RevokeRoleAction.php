<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\{User, Role};
use App\Repositories\Interfaces\{UserRepositoryExtendedInterface, RoleRepositoryInterface};
use App\Services\EmailService;
use App\Exceptions\Users\UserNotFoundException;
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException, UserAlreadyHasRoleException};

// ═══════════════════════════════════════════════════════════
// RevokeRoleAction
// ═══════════════════════════════════════════════════════════

class RevokeRoleAction
{
    public function __construct(
        private readonly UserRepositoryExtendedInterface $userRepository,
        private readonly RoleRepositoryInterface         $roleRepository,
    ) {}

    public function __invoke(User $user, string $roleId, ?string $branchId): void
    {
        $role = $this->roleRepository->findById($roleId);

        if (! $role) {
            throw new RoleNotFoundException("El rol no existe.");
        }

        // Evitar que el último admin de la empresa sea revocado
        if ($role->name === 'admin') {
            $adminCount = $this->userRepository
                ->getUsersWithRole($roleId, $user->company_id)
                ->total();

            if ($adminCount <= 1) {
                throw new \DomainException(
                    'No podés revocar el único administrador de la empresa.'
                );
            }
        }

        $this->userRepository->revokeRole($user, $role, $branchId);
    }
}
