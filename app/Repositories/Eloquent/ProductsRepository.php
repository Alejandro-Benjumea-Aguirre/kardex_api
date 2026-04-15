<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Products;
use App\Repositories\Interfaces\ProductsRepositoryExtendedInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsRepository implements ProductsRepositoryExtendedInterface
{
    // ─── BÚSQUEDAS ────────────────────────────────────────

    public function findById(string $id): ?Products
    {
        return Products::with(['company', 'category'])->find($id);
    }

    public function findByCategory(string $category_id): Collection
    {
        return Products::with(['company', 'category'])
                    ->where('category_id', $category_id)
                    ->get();
    }

    // ─── CRUD ─────────────────────────────────────────────

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Products::with(['company', 'category'])
            ->when(
                $filters['company_id'] ?? null,
                fn($q, $v) => $q->where('company_id', $v)
            )
            ->when(
                $filters['category_id'] ?? null,
                fn($q, $v) => $q->where('category_id', $v)
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

    public function create(array $data): Products
    {
        return Products::create($data);
    }

    public function update(Products $product, array $data): Products
    {
        $product->update($data);
        return $product->fresh(['company', 'category']);
    }

    public function deactivate(Products $product): void
    {
        $product->update(['is_active' => false]);
    }

    public function activate(Products $product): void
    {
        $product->update(['is_active' => true]);
    }
}
