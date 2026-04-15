<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryExtendedInterface;

class DeactivateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryExtendedInterface $categoryRepository,
    ) {}

    public function __invoke(Category $category): void
    {
        $this->categoryRepository->deactivate($category);
    }
}
