<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Data\Category\UpdateCategoryData;
use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryExtendedInterface;

class UpdateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryExtendedInterface $categoryRepository,
    ) {}

    public function __invoke(Category $category, UpdateCategoryData $data): Category
    {
        $fields = array_filter($data->toArray(), fn($v) => $v !== null);

        return $this->categoryRepository->update($category, $fields);
    }
}
