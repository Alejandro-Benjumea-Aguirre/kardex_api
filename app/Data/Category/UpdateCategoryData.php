<?php

declare(strict_types=1);

namespace App\Data\Category;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

// ─── UpdateUserData ─────────────────────────────────────────

class UpdateCategoryData extends Data
{
    public function __construct(
        public readonly string|Optional          $name = new Optional(),
        public readonly string|Optional          $description = new Optional(),
        public readonly string|null|Optional     $slug      = new Optional(),
        public readonly string|null|Optional     $image_url = new Optional(),
        public readonly uuid|Optional          $parent_id = new Optional(),
    ) {}
}
