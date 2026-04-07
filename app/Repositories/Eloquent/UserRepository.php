<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\{User, Role};
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryExtendedInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface, UserRepositoryExtendedInterface
{
    // ─── BÚSQUEDAS ────────────────────────────────────────

    public function findById(string $id): ?User
    {
        return User::with(['company', 'roles.permissions', 'branches'])->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', strtolower(trim($email)))->first();
    }

    public function findByEmailVerificationToken(string $token): ?User
    {
        return User::where('email_verification_token', $token)
                   ->where('email_verification_expires_at', '>', now())
                   ->first();
    }

    public function findByPasswordResetToken(string $token): ?User
    {
        $reset = \DB::table('password_reset_tokens')
            ->where('token', hash('sha256', $token))
            ->where('created_at', '>', now()->subHour())
            ->first();

        if (! $reset) {
            return null;
        }

        return $this->findByEmail($reset->email);
    }

    // ─── CRUD ─────────────────────────────────────────────

    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return User::with(['company', 'roles'])
            ->when(
                $filters['company_id'] ?? null,
                fn($q, $v) => $q->where('company_id', $v)
            )
            ->when(
                $filters['search'] ?? null,
                fn($q, $v) => $q->where(fn($inner) =>
                    $inner->where('first_name', 'ilike', "%{$v}%")
                          ->orWhere('last_name',  'ilike', "%{$v}%")
                          ->orWhere('email',      'ilike', "%{$v}%")
                )
            )
            ->when(
                $filters['is_active'] ?? null,
                fn($q, $v) => $q->where('is_active', filter_var($v, FILTER_VALIDATE_BOOLEAN))
            )
            ->when(
                $filters['role_id'] ?? null,
                fn($q, $v) => $q->whereHas('roles', fn($r) => $r->where('roles.id', $v))
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        return User::create(array_merge($data, [
            'email' => strtolower(trim($data['email'])),
        ]));
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh(['company', 'roles']);
    }

    public function deactivate(User $user): void
    {
        $user->update(['is_active' => false]);

        app(\App\Services\TokenService::class)->revokeAllUserTokens($user->id);
    }

    public function activate(User $user): void
    {
        $user->update(['is_active' => true]);
    }

    // ─── TOKENS DE VERIFICACIÓN ───────────────────────────

    public function setEmailVerificationToken(User $user, string $token): void
    {
        cache()->put(
            "email_verify:{$user->id}",
            hash('sha256', $token),
            now()->addHours(24)
        );
    }

    public function markEmailAsVerified(User $user): void
    {
        $user->update([
            'is_email_verified' => true,
            'email_verified_at' => now(),
        ]);

        cache()->forget("email_verify:{$user->id}");
    }

    public function setPasswordResetToken(User $user, string $token, \DateTimeInterface $expiresAt): void
    {
        \DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'token'      => hash('sha256', $token),
                'created_at' => now(),
            ]
        );
    }

    public function clearPasswordResetToken(User $user): void
    {
        \DB::table('password_reset_tokens')
            ->where('email', $user->email)
            ->delete();
    }

    // ─── ESTADO ───────────────────────────────────────────

    public function updateLastLogin(User $user): void
    {
        $user->updateQuietly(['last_login_at' => now()]);
    }

    public function incrementFailedLogins(User $user): void
    {
        $key   = "failed_logins:{$user->id}";
        $count = (int) cache()->get($key, 0) + 1;
        cache()->put($key, $count, now()->addMinutes(15));
    }

    public function resetFailedLogins(User $user): void
    {
        cache()->forget("failed_logins:{$user->id}");
    }

    public function getFailedLoginCount(User $user): int
    {
        return (int) cache()->get("failed_logins:{$user->id}", 0);
    }

    // ─── GESTIÓN DE ROLES ─────────────────────────────────

    public function assignRole(User $user, Role $role, ?string $branchId, ?string $assignedBy): void
    {
        $user->roles()->syncWithoutDetaching([
            $role->id => [
                'branch_id'   => $branchId,
                'assigned_by' => $assignedBy,
                'expires_at'  => null,
            ],
        ]);

        $user->invalidatePermissionsCache($branchId);
    }

    public function revokeRole(User $user, Role $role, ?string $branchId): void
    {
        $user->roles()
             ->wherePivot('branch_id', $branchId)
             ->detach($role->id);

        $user->invalidatePermissionsCache($branchId);
    }

    public function syncRoles(User $user, array $roleIds, ?string $branchId): void
    {
        $currentRoleIds = $user->roles()
            ->wherePivot('branch_id', $branchId)
            ->pluck('roles.id')
            ->all();

        $toAdd    = array_diff($roleIds, $currentRoleIds);
        $toRemove = array_diff($currentRoleIds, $roleIds);

        foreach ($toAdd as $roleId) {
            $user->roles()->attach($roleId, ['branch_id' => $branchId]);
        }

        if ($toRemove) {
            $user->roles()
                 ->wherePivot('branch_id', $branchId)
                 ->detach($toRemove);
        }

        $user->invalidatePermissionsCache($branchId);
    }

    public function getUsersWithRole(string $roleId, ?string $companyId = null): LengthAwarePaginator
    {
        return User::whereHas('roles', fn($q) => $q->where('roles.id', $roleId))
            ->when($companyId, fn($q, $v) => $q->byCompany($v))
            ->with('roles')
            ->paginate(20);
    }
}
