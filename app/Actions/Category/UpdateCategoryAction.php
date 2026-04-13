<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Data\Category\UpdateCategoryData;
use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryExtendedInterface;
use Spatie\LaravelData\Optional;

// ═══════════════════════════════════════════════════════════
// UpdateUserAction
// ═══════════════════════════════════════════════════════════

class UpdateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryExtendedInterface $categoryRepository,
    ) {}

    public function __invoke(Category $category, UpdateCategoryData $data, Category $updatedBy): Category
    {

        $fields = $data->toArray();

        // Email: solo se actualiza si el updater tiene permiso
        if (isset($fields['email']) && ! $updatedBy->hasPermission('category:update')) {
            unset($fields['email']);
        }

        $allowedFields = array_filter($fields, fn($v) => $v !== null);

        return $this->categoryRepository->update($category, $allowedFields);
    }
}
