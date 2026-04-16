<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Inventary;
use App\Repositories\Interfaces\InventaryRepositoryExtendedInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class InventaryRepository implements InventaryRepositoryExtendedInterface
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Inventary::with(['branch', 'variant'])
            ->when(
                $filters['company_id'] ?? null,
                fn($q, $v) => $q->whereHas('branch', fn($b) => $b->where('company_id', $v))
            )
            ->when(
                $filters['search'] ?? null,
                fn($q, $v) => $q->whereHas('variant', fn($inner) =>
                    $inner->where('name', 'ilike', "%{$v}%")
                          ->orWhere('sku',  'ilike', "%{$v}%")
                )
            )
            ->when(
                isset($filters['is_active']) && $filters['is_active'] !== null,
                fn($q) => $q->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN))
            )
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findById(string $id): ?Inventary
    {
        return Inventary::with(['branch', 'variant'])->find($id);
    }

    public function create(array $data): Inventary
    {
        return Inventary::create($data);
    }

    public function update(Inventary $inventary, array $data): Inventary
    {
        $inventary->update($data);
        return $inventary->fresh(['branch', 'variant']);
    }

    public function activate(Inventary $inventary): void
    {
        $inventary->update(['is_active' => true]);
    }

    public function deactivate(Inventary $inventary): void
    {
        $inventary->update(['is_active' => false]);
    }
}
