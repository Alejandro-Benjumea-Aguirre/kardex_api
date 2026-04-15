<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Category;
use App\Repositories\Interfaces\CategoryRepositoryExtendedInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryRepository implements CategoryRepositoryExtendedInterface
{
    // ─── BÚSQUEDAS ────────────────────────────────────────

    public function findById(string $id): ?Category
    {
        return Category::with(['company'])->find($id);
    }

    public function findByParent(string $parent_id): Collection
    {
        return Category::with(['company'])
                    ->where('parent_id', $parent_id)
                    ->get();
    }

    // ─── CRUD ─────────────────────────────────────────────

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Category::with(['company'])
            ->when(
                $filters['company_id'] ?? null,
                fn($q, $v) => $q->where('company_id', $v)
            )
            ->when(
                $filters['search'] ?? null,
                fn($q, $v) => $q->where(fn($inner) =>
                    $inner->where('name', 'ilike', "%{$v}%")
                          ->orWhere('description',  'ilike', "%{$v}%")
                )
            )
            ->when(
                $filters['is_active'] ?? null,
                fn($q, $v) => $q->where('is_active', filter_var($v, FILTER_VALIDATE_BOOLEAN))
            )
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): Category
    {
        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category->fresh(['company']);
    }

    public function deactivate(Category $category): void
    {
        $category->update(['is_active' => false]);
    }

    public function activate(Category $category): void
    {
        $category->update(['is_active' => true]);
    }
}
