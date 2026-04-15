<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\Branch;
use App\Repositories\Interfaces\BranchRepositoryExtendedInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BranchRepository implements BranchRepositoryExtendedInterface
{
    // ─── BÚSQUEDAS ────────────────────────────────────────

    public function findById(string $id): ?Branch
    {
        return Branch::with(['company', 'users'])->find($id);
    }

    public function findByCode(string $code): ?Branch
    {
        return Branch::with(['company'])
                     ->where('code', $code)
                     ->first();
    }

    public function findByCompany(string $companyId): Collection
    {
        return Branch::with(['company'])
                     ->where('company_id', $companyId)
                     ->orderBy('is_main', 'desc')
                     ->orderBy('name')
                     ->get();
    }

    public function findMainByCompany(string $companyId): ?Branch
    {
        return Branch::with(['company'])
                     ->where('company_id', $companyId)
                     ->where('is_main', true)
                     ->first();
    }

    // ─── CRUD ─────────────────────────────────────────────

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return Branch::with(['company'])
            ->when(
                $filters['company_id'] ?? null,
                fn($q, $v) => $q->where('company_id', $v)
            )
            ->when(
                $filters['search'] ?? null,
                fn($q, $v) => $q->where(fn($inner) =>
                    $inner->where('name',    'ilike', "%{$v}%")
                          ->orWhere('code',  'ilike', "%{$v}%")
                          ->orWhere('city',  'ilike', "%{$v}%")
                          ->orWhere('email', 'ilike', "%{$v}%")
                )
            )
            ->when(
                $filters['is_active'] ?? null,
                fn($q, $v) => $q->where('is_active', filter_var($v, FILTER_VALIDATE_BOOLEAN))
            )
            ->when(
                $filters['city'] ?? null,
                fn($q, $v) => $q->where('city', $v)
            )
            ->orderBy('is_main', 'desc')
            ->orderBy('name')
            ->paginate($perPage);
    }

    public function create(array $data): Branch
    {
        return Branch::create($data);
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);
        return $branch->fresh(['company']);
    }

    // ─── ESTADO ───────────────────────────────────────────

    public function activate(Branch $branch): void
    {
        $branch->update(['is_active' => true]);
    }

    public function deactivate(Branch $branch): void
    {
        $branch->update(['is_active' => false]);
    }

    public function setAsMain(Branch $branch): void
    {
        // Quita is_main a todas las demás sucursales de la empresa
        $this->clearMainByCompany(
            companyId: $branch->company_id,
            excludeId: $branch->id,
        );

        $branch->update(['is_main' => true]);
    }

    public function clearMainByCompany(string $companyId, ?string $excludeId = null): void
    {
        Branch::where('company_id', $companyId)
              ->where('is_main', true)
              ->when(
                  $excludeId,
                  fn($q) => $q->where('id', '!=', $excludeId)
              )
              ->update(['is_main' => false]);
    }

    // ─── USUARIOS ─────────────────────────────────────────

    public function attachUser(Branch $branch, string $userId, bool $isDefault = false): void
    {
        $branch->users()->attach($userId, [
            'is_default'  => $isDefault,
            'assigned_at' => now(),
        ]);
    }

    public function detachUser(Branch $branch, string $userId): void
    {
        $branch->users()->detach($userId);
    }

    public function hasUser(Branch $branch, string $userId): bool
    {
        return $branch->users()
                      ->where('user_id', $userId)
                      ->exists();
    }
}