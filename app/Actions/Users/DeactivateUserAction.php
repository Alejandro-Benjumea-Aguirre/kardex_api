<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\{User, Role};
use App\Repositories\Interfaces\{UserRepositoryExtendedInterface, RoleRepositoryInterface};
use App\Services\EmailService;
use App\Exceptions\Users\UserNotFoundException;
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException, UserAlreadyHasRoleException};

// ═══════════════════════════════════════════════════════════
// DeactivateUserAction
// ═══════════════════════════════════════════════════════════

class DeactivateUserAction
{
    public function __construct(
        private readonly UserRepositoryExtendedInterface $userRepository,
    ) {}

    public function __invoke(User $user, User $deactivatedBy): void
    {
        // No puede desactivarse a sí mismo
        if ($user->id === $deactivatedBy->id) {
            throw new \DomainException('No podés desactivar tu propia cuenta.');
        }

        $this->userRepository->deactivate($user);
    }
}
