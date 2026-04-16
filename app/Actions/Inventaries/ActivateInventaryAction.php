<?php

declare(strict_types=1);

namespace App\Actions\Inventaries;

use App\Models\Inventary;
use App\Repositories\Interfaces\{InventaryRepositoryExtendedInterface, RoleRepositoryInterface};
use App\Exceptions\Inventaries\InventaryNotFoundException;
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException, CategoryAlreadyHasRoleException};

// ═══════════════════════════════════════════════════════════
// ActivateCategoryAction
// ═══════════════════════════════════════════════════════════

class ActivateInventaryAction
{
    public function __construct(
        private readonly InventaryRepositoryExtendedInterface $inventaryRepository,
    ) {}

    public function __invoke(Inventary $inventary): void
    {
        $this->inventaryRepository->activate($inventary);
    }
}
