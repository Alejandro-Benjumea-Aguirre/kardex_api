<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, HasMany};
use Illuminate\Database\Eloquent\SoftDeletes;

// ═══════════════════════════════════════════════════════════
// Role Model
//
// CONCEPTO: Roles globales vs roles de empresa
// ═══════════════════════════════════════════════════════════
//
// company_id = NULL  → rol global del sistema (ej: super_admin)
// company_id = UUID  → rol de una empresa específica
//
// Los roles del sistema (is_system = true) no pueden
// ser modificados ni borrados por admins de empresa.
// Solo el super_admin puede tocarlos.
// ═══════════════════════════════════════════════════════════

class Role extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'display_name',
        'description',
        'is_default',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_system'  => 'boolean',
            'is_active'  => 'boolean',
        ];
    }

    // ─── RELACIONES ───────────────────────────────────────

    // Rol pertenece a una empresa (nullable = global)
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Permisos asignados a este rol (M:M via role_permissions)
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'role_permissions',
            'role_id',
            'permission_id'
        )
        ->withPivot('granted_by')
        ->withTimestamps();
    }

    // Usuarios que tienen este rol (M:M via user_roles)
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'user_roles',
            'role_id',
            'user_id'
        )
        ->withPivot(['branch_id', 'expires_at', 'assigned_by'])
        ->withTimestamps();
    }

    // ─── SCOPES ───────────────────────────────────────────

    // Solo roles de una empresa específica + roles globales
    public function scopeForCompany($query, string $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhereNull('company_id');
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    // ─── MÉTODOS DE NEGOCIO ───────────────────────────────

    public function isSystem(): bool
    {
        return $this->is_system === true;
    }

    public function isGlobal(): bool
    {
        return $this->company_id === null;
    }

    public function isEditableBy(User $user): bool
    {
        // Los roles del sistema solo los puede editar el super_admin
        if ($this->isSystem()) {
            return $user->hasPermission('system:manage');
        }

        // Los roles de empresa los puede editar quien tenga el permiso
        return $user->hasPermission('role:update');
    }

    // Devuelve los nombres de los permisos como array plano
    // Útil para cachear y comparar
    public function getPermissionNames(): array
    {
        return $this->permissions->pluck('name')->all();
    }

    // ─── INVALIDAR CACHÉ DE TODOS LOS USUARIOS DEL ROL ───
    //
    // Cuando cambian los permisos de un rol, hay que invalidar
    // el caché de permisos de TODOS los usuarios que tienen ese rol.
    // Esto es costoso si el rol tiene miles de usuarios, pero
    // el cambio de permisos es una operación poco frecuente.
    public function invalidateUsersPermissionsCache(): void
    {
        $this->users()
             ->select('users.id')
             ->each(function (User $user) {
                 $user->invalidatePermissionsCache();
             });
    }
}
