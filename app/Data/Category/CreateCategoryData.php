<?php

declare(strict_types=1);

namespace App\Data\Category;

use Spatie\LaravelData\Data;

class CreateCategoryData extends Data
{
    public function __construct(
        public readonly string  $name,
        public readonly string  $description,
        public readonly string  $image_url,
        public readonly string  $slug,
        public readonly ?string $company_id,
        public readonly ?string $parent_id,
    ) {}
}
