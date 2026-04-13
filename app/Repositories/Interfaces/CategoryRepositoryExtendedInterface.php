<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

// ═══════════════════════════════════════════════════════════
// UserRepositoryInterface

interface CategoryRepositoryExtendedInterface
{

    public function paginate(array $filters, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator;
    public function findById(string $id): ?\App\Models\Category;
    public function findByParent(string $id): ?\App\Models\Category;
    public function create(array $data): \App\Models\Category;
    public function update(\App\Models\User $category, array $data): \App\Models\Category;
    public function deactivate(\App\Models\Category; $category): void;
    public function activate(\App\Models\Category; $category): void;

}
