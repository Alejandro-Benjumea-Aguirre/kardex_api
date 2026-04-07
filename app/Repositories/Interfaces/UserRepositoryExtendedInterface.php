<?php

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════
// INTERFACES
// ═══════════════════════════════════════════════════════════

namespace App\Repositories\Interfaces;

use App\Models\{Role, Permission, User};
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryExtendedInterface
{
    // Roles
    public function assignRole(User $user, Role $role, ?string $branchId, ?string $assignedBy): void;
    public function revokeRole(User $user, Role $role, ?string $branchId): void;
    public function syncRoles(User $user, array $roleIds, ?string $branchId): void;
    public function getUsersWithRole(string $roleId, ?string $companyId = null): \Illuminate\Pagination\LengthAwarePaginator;

    // CRUD
    public function paginate(array $filters, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator;
    public function findById(string $id): ?\App\Models\User;
    public function create(array $data): \App\Models\User;
    public function update(\App\Models\User $user, array $data): \App\Models\User;
    public function deactivate(\App\Models\User $user): void;
    public function activate(\App\Models\User $user): void;
}
