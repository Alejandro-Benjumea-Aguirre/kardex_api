<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use App\Repositories\Interfaces\{CategoryRepositoryExtendedInterface, RoleRepositoryInterface};
use App\Exceptions\Category\CategoryNotFoundException;
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException, CategoryAlreadyHasRoleException};

// ═══════════════════════════════════════════════════════════
// ActivateCategoryAction
// ═══════════════════════════════════════════════════════════

class ActivateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryExtendedInterface $categoryRepository,
    ) {}

    public function __invoke(Category $category): void
    {
        $this->categoryRepository->activate($category);
    }
}
