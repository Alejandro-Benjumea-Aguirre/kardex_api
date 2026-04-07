<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany};

// ═══════════════════════════════════════════════════════════
// Permission Model
//
// CONCEPTO: Convención de nombres módulo:acción
// ═══════════════════════════════════════════════════════════
//
// Todos los permisos del sistema siguen la convención:
//   {módulo}:{acción}
//
// Módulos y sus permisos:
//   products:   read, create, update, delete, import, export
//   sales:      read, create, void, refund, discount
//   inventory:  read, manage, transfer, adjust
//   purchases:  read, create, approve, receive
//   users:      read, create, update, delete, assign-roles
//   roles:      read, create, update, delete
//   reports:    view, export
//   settings:   manage
//   system:     access, manage   ← solo super_admin
// ═══════════════════════════════════════════════════════════

class Permission extends Model
{
    use HasUuids;

    // Los permisos del sistema raramente cambian
    // No necesitan SoftDeletes — si se borra, se borra
    protected $fillable = [
        'company_id',
        'name',
        'display_name',
        'description',
        'module',
        'sort_order',
        'is_system',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_system'  => 'boolean',
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    // ─── RELACIONES ───────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Roles que tienen este permiso
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permissions',
            'permission_id',
            'role_id'
        )->withTimestamps();
    }

    // ─── SCOPES ───────────────────────────────────────────

    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Permisos disponibles para una empresa (propios + globales)
    public function scopeForCompany($query, string $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhereNull('company_id');
        });
    }

    // ─── HELPERS ESTÁTICOS ────────────────────────────────

    // Todos los módulos disponibles
    public static function modules(): array
    {
        return [
            'products',
            'sales',
            'inventory',
            'purchases',
            'customers',
            'suppliers',
            'users',
            'roles',
            'reports',
            'settings',
            'system',
        ];
    }

    // Parsear módulo de un nombre de permiso
    // "product:create" → "product"
    public function getModuleFromName(): string
    {
        return explode(':', $this->name)[0] ?? $this->module;
    }

    // Parsear acción de un nombre de permiso
    // "product:create" → "create"
    public function getActionFromName(): string
    {
        return explode(':', $this->name)[1] ?? '';
    }
}
