<?php

declare(strict_types=1);

namespace App\Actions\Category;

use App\Data\Category\CreateCategoryData;
use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryExtendedInterface;

class CreateCategoryAction
{
    public function __construct(
        private readonly CategoryRepositoryExtendedInterface $categoryRepository,
    ) {}

    public function __invoke(CreateCategoryData $data): Category
    {
        return $this->categoryRepository->create([
            'company_id'  => $data->company_id,
            'name'        => $data->name,
            'description' => $data->description,
            'slug'        => $data->slug,
            'image_url'   => $data->image_url,
            'parent_id'   => $data->parent_id,
            'is_active'   => true,
        ]);
    }
}
