<?php

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════
// INTERFACES
// ═══════════════════════════════════════════════════════════

namespace App\Repositories\Interfaces;

use App\Models\{Role, Permission, User};
use Illuminate\Database\Eloquent\Collection;

interface RoleRepositoryInterface
{
    public function findById(string $id): ?Role;
    public function findByName(string $name, ?string $companyId = null): ?Role;
    public function allForCompany(string $companyId, bool $includeGlobal = true): Collection;
    public function create(array $data): Role;
    public function update(Role $role, array $data): Role;
    public function delete(Role $role): void;
    public function syncPermissions(Role $role, array $permissionIds, ?string $grantedBy = null): void;
    public function getPermissionIds(Role $role): array;
}
