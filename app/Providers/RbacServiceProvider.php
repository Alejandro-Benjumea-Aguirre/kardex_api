<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

// ── Interfaces
use App\Repositories\Interfaces\{
    RoleRepositoryInterface,
    PermissionRepositoryInterface,
    UserRepositoryExtendedInterface,
};

// ── Implementaciones
use App\Repositories\Eloquent\{
    RoleRepository,
    PermissionRepository,
    UserRepository,
};

class RbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {

        $this->app->bind(RoleRepositoryInterface::class,       RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(UserRepositoryExtendedInterface::class, UserRepository::class);
    }

    public function boot(): void
    {

        Gate::define('update-user', function (User $auth, User $target) {
            // Un usuario siempre puede editarse a sí mismo
            if ($auth->id === $target->id) {
                return true;
            }
            // Para editar a otro, necesita el permiso Y que sea de su empresa
            return $auth->hasPermission('users:update')
                && $auth->company_id === $target->company_id;
        });

        Gate::define('assign-roles', function (User $auth, User $target) {
            // No puede asignarse roles a sí mismo (previene escalada de privilegios)
            if ($auth->id === $target->id) {
                return false;
            }
            return $auth->hasPermission('users:assign-roles')
                && $auth->company_id === $target->company_id;
        });

        Gate::define('manage-role', function (User $auth, \App\Models\Role $role) {
            // Los roles del sistema solo los gestiona el super_admin
            if ($role->isSystem()) {
                return $auth->hasPermission('system:manage');
            }
            // Los roles de empresa los gestiona quien tenga el permiso
            // Y que el rol sea de su empresa
            return $auth->hasPermission('roles:update')
                && ($role->company_id === $auth->company_id || $role->isGlobal());
        });

        Gate::before(function (User $user) {
            if ($user->hasPermission('system:manage')) {
                return true; // super_admin: acceso total
            }
            return null; // Continuar con el Gate normal
        });
    }
}
