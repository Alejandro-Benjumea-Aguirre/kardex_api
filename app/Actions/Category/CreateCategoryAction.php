<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Data\Users\CreateCategoryData;
use App\Models\{Category};
use App\Repositories\Interfaces\{CategoryRepositoryExtendedInterface};
use App\Exceptions\Users\CategoryNotFoundException;
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException, UserAlreadyHasRoleException};

// ═══════════════════════════════════════════════════════════
// CreateUserAction
// ═══════════════════════════════════════════════════════════

class CreateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryExtendedInterface $categoryRepository,
    ) {}

    public function __invoke(CreateCateogryData $data, Category $createdBy): Category
    {
        $category = $this->categoryRepository->create([
            'company_id'        => $data->company_id,
            'name'              => $data->name,
            'descripton'        => $data->description,
            'slug'              => $data->slug,
            'image_url'         => $data->image_url,
            'parent_id'         => $data->parent_id ?? '',
            'is_active'         => true,
        ]);

        return $category;
    }
}
