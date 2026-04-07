<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Models\{User, Role};
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryExtendedInterface;
use Illuminate\Pagination\LengthAwarePaginator;

// ═══════════════════════════════════════════════════════════
// UserRepository — Implementación con Eloquent
//
// CONCEPTO: ¿Por qué centralizar las queries aquí?
// ═══════════════════════════════════════════════════════════
//
// ERROR COMÚN: Escribir las queries directamente en el Controller:
//
//   // En AuthController:
//   $user = User::where('email', $email)
//               ->where('is_active', true)
//               ->first();
//
//   // En ProfileController:
//   $user = User::where('email', $email)
//               ->where('is_active', true)
//               ->first();
//
// Misma query duplicada en 5 lugares. Si necesitás agregar
// ->where('is_email_verified', true), la buscás en 5 archivos.
//
// CON REPOSITORY:
//   $user = $this->userRepo->findByEmail($email);
//
// Una sola implementación, usada en todos lados.
// El cambio se hace en un solo lugar.
// ═══════════════════════════════════════════════════════════

class UserRepository implements UserRepositoryInterface, UserRepositoryExtendedInterface
{
    // ─── BÚSQUEDAS ────────────────────────────────────────

    public function findById(string $id): ?User
    {
        // Cargamos relaciones completas para que el objeto sea útil
        // tanto en auth como en la gestión de usuarios/roles.
        return User::with(['company', 'roles.permissions', 'branches'])->find($id);
    }

    public function findByEmail(string $email): ?User
    {
        // Normalizamos el email a minúsculas aquí, en el repositorio.
        // Así no importa si el usuario escribe MARIA@GMAIL.COM o maria@gmail.com
        // — siempre encontramos el mismo registro.
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
        // Laravel tiene su propio sistema de password reset (PasswordBroker)
        // que guarda tokens en la tabla password_reset_tokens.
        // Aquí hacemos la query directamente para mayor control.
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
        // ─── CONCEPTO: Query Builder dinámico con when() ──────
        //
        // when($condición, $callback) agrega la condición al query
        // SOLO si $condición es truthy. Evita el antipatrón:
        //
        //   $query = User::query();
        //   if ($filters['search']) { $query->where(...); }
        //   if ($filters['role'])   { $query->where(...); }
        //
        // Con when() es mucho más limpio y encadenable.
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
                // ILIKE en PostgreSQL = LIKE pero case-insensitive
                // En MySQL usarías LIKE directamente (es case-insensitive por defecto)
            )
            ->when(
                $filters['is_active'] ?? null,
                fn($q, $v) => $q->where('is_active', filter_var($v, FILTER_VALIDATE_BOOLEAN))
            )
            ->when(
                $filters['role_id'] ?? null,
                fn($q, $v) => $q->whereHas('roles', fn($r) => $r->where('roles.id', $v))
                // whereHas: filtra usuarios que TENGAN al menos un rol con ese ID
                // Genera un WHERE EXISTS (SELECT 1 FROM user_roles WHERE ...)
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($perPage);
    }

    public function create(array $data): User
    {
        // El cast 'hashed' en el Model hashea el password automáticamente.
        // No necesitamos Hash::make() aquí.
        // Normalizamos el email al crear para consistencia en la DB.
        return User::create(array_merge($data, [
            'email' => strtolower(trim($data['email'])),
        ]));
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        // fresh() recarga el modelo desde la DB con sus relaciones.
        // update() no actualiza los atributos del objeto en memoria.
        return $user->fresh(['company', 'roles']);
    }

    public function deactivate(User $user): void
    {
        $user->update(['is_active' => false]);

        // Revocar todos sus tokens activos.
        // Un usuario desactivado no debe poder seguir usando sesiones activas.
        app(\App\Services\TokenService::class)->revokeAllUserTokens($user->id);
    }

    public function activate(User $user): void
    {
        $user->update(['is_active' => true]);
    }

    // ─── TOKENS DE VERIFICACIÓN ───────────────────────────

    public function setEmailVerificationToken(User $user, string $token): void
    {
        // Guardamos el token en caché con TTL de 24 horas.
        // Clave: "email_verify:{userId}" → valor: token hasheado
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
        // Usamos la tabla estándar de Laravel para compatibilidad con
        // el PasswordBroker. Token guardado como SHA-256 (nunca en texto plano).
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
        // updateQuietly: no dispara eventos del Model (Updating, Updated).
        // Útil para updates de auditoría que no deben disparar side effects.
        $user->updateQuietly(['last_login_at' => now()]);
    }

    public function incrementFailedLogins(User $user): void
    {
        // Guardamos intentos fallidos en caché, no en DB.
        // Razón: se resetean frecuentemente y no necesitan persistencia.
        // TTL: 15 minutos — después de ese tiempo se resetea solo.
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
        // ─── CONCEPTO: attach() con pivot data ────────────────
        //
        // Nuestra tabla pivote user_roles tiene columnas extra:
        //   branch_id, expires_at, assigned_by
        //
        // syncWithoutDetaching() es idempotente: si el rol ya está
        // asignado en ese scope, no lanza error de UNIQUE constraint.
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
        // wherePivot filtra por branch_id para no revocar otros scopes
        $user->roles()
             ->wherePivot('branch_id', $branchId)
             ->detach($role->id);

        $user->invalidatePermissionsCache($branchId);
    }

    public function syncRoles(User $user, array $roleIds, ?string $branchId): void
    {
        // Sincroniza SOLO los roles del scope indicado.
        // Los roles de otros branches no se tocan.
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
