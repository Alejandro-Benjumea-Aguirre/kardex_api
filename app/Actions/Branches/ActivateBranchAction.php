<?php

declare(strict_types=1);

namespace App\Actions\Branch;

use App\Models\Branch;
use App\Repositories\Interfaces\{BranchRepositoryExtendedInterface, RoleRepositoryInterface};
use App\Exceptions\Branch\BranchNotFoundException;
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException, CategoryAlreadyHasRoleException};

// ═══════════════════════════════════════════════════════════
// ActivateCategoryAction
// ═══════════════════════════════════════════════════════════

class ActivateBranchAction
{
    public function __construct(
        private readonly BranchRepositoryExtendedInterface $branchRepository,
    ) {}

    public function __invoke(Branch $branch): void
    {
        $this->branchRepository->activate($branch);
    }
}
