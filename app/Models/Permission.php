<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany};

class Permission extends Model
{
    use HasUuids;

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
