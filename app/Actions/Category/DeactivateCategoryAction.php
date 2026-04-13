<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\{Category};
use App\Repositories\Interfaces\{CategoryRepositoryExtendedInterface, RoleRepositoryInterface};
use App\Exceptions\Users\CategoryNotFoundException;
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException, UserAlreadyHasRoleException};

// ═══════════════════════════════════════════════════════════
// DeactivateUserAction
// ═══════════════════════════════════════════════════════════

class DeactivateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryExtendedInterface $categoryRepository,
    ) {}

    public function __invoke(Category $category, Category $deactivatedBy): void
    {
        // No puede desactivarse categorias con productos
        if ($category->id === $deactivatedBy->id) {
            throw new \DomainException('No podés desactivar una  categoria si cuenta con productos con stock disponible.');
        }

        $this->categoryRepository->deactivate($category);
    }
}
