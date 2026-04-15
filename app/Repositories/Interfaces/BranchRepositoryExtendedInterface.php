<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

// ═══════════════════════════════════════════════════════════
// BranchRepositoryExtendedInterface

interface BranchRepositoryExtendedInterface
{
    // ─── BÚSQUEDAS ───────────────────────────────────────
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator;
    public function findById(string $id): ?Branch;
    public function findByCode(string $code): ?Branch;
    public function findByCompany(string $companyId): Collection;
    public function findMainByCompany(string $companyId): ?Branch;

    // ─── CRUD ────────────────────────────────────────────
    public function create(array $data): Branch;
    public function update(Branch $branch, array $data): Branch;

    // ─── ESTADO ──────────────────────────────────────────
    public function activate(Branch $branch): void;
    public function deactivate(Branch $branch): void;
    public function setAsMain(Branch $branch): void;
    public function clearMainByCompany(string $companyId, ?string $excludeId = null): void;

    // ─── USUARIOS ────────────────────────────────────────
    public function attachUser(Branch $branch, string $userId, bool $isDefault = false): void;
    public function detachUser(Branch $branch, string $userId): void;
    public function hasUser(Branch $branch, string $userId): bool;
}