<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Models\{User, Role};
use App\Repositories\Interfaces\{UserRepositoryExtendedInterface, RoleRepositoryInterface};
use App\Services\EmailService;
use App\Exceptions\Users\UserNotFoundException;
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException, UserAlreadyHasRoleException};

// ═══════════════════════════════════════════════════════════
// ActivateUserAction
// ═══════════════════════════════════════════════════════════

class ActivateUserAction
{
    public function __construct(
        private readonly UserRepositoryExtendedInterface $userRepository,
    ) {}

    public function __invoke(User $user): void
    {
        $this->userRepository->activate($user);
    }
}
