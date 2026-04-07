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

// ═══════════════════════════════════════════════════════════
// RbacServiceProvider
//
// CONCEPTO: ¿Por qué registrar en un ServiceProvider separado?
// ═══════════════════════════════════════════════════════════
//
// AppServiceProvider → bindings globales de la app
// RbacServiceProvider → todo lo relacionado con RBAC
//
// Separar por dominio facilita:
//   ✅ Encontrar dónde está cada binding
//   ✅ Desactivar un módulo entero sin tocar el resto
//   ✅ Testear el módulo en aislamiento
//
// REGISTRAR EN bootstrap/app.php:
//   ->withProviders([
//       App\Providers\RbacServiceProvider::class,
//   ])
// ═══════════════════════════════════════════════════════════

class RbacServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings Interface → Implementación concreta
        // El Container inyecta automáticamente la implementación
        // correcta cada vez que alguien pide la interface
        $this->app->bind(RoleRepositoryInterface::class,       RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(UserRepositoryExtendedInterface::class, UserRepository::class);
    }

    public function boot(): void
    {
        // ─── GATES DE LARAVEL ─────────────────────────────
        //
        // CONCEPTO: Gates vs Middleware de permisos
        //
        // PermissionMiddleware → protege RUTAS completas
        //   Ideal para: "solo usuarios con products:create pueden
        //   llegar al endpoint POST /products"
        //
        // Gates → verificación granular DENTRO del código
        //   Ideal para: "este usuario puede editar ESTE producto
        //   específico" (lógica que depende del recurso, no solo del rol)
        //
        // Usamos Gates para operaciones que tienen lógica adicional:
        //   - ¿Puede editar este usuario? Solo si es de su empresa
        //   - ¿Puede borrar este rol? Solo si no tiene usuarios
        //
        // Gate::define() registra la verificación.
        // Se usa en Controllers con: $this->authorize('update-user', $user)
        // O en Blade: @can('update-user', $user)

        // Gate: ¿Puede este usuario editar a OTRO usuario?
        Gate::define('update-user', function (User $auth, User $target) {
            // Un usuario siempre puede editarse a sí mismo
            if ($auth->id === $target->id) {
                return true;
            }
            // Para editar a otro, necesita el permiso Y que sea de su empresa
            return $auth->hasPermission('users:update')
                && $auth->company_id === $target->company_id;
        });

        // Gate: ¿Puede asignar roles?
        Gate::define('assign-roles', function (User $auth, User $target) {
            // No puede asignarse roles a sí mismo (previene escalada de privilegios)
            if ($auth->id === $target->id) {
                return false;
            }
            return $auth->hasPermission('users:assign-roles')
                && $auth->company_id === $target->company_id;
        });

        // Gate: ¿Puede gestionar este rol?
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

        // ─── BEFORE GATE: super_admin pasa todo ──────────
        //
        // Gate::before() se ejecuta ANTES que cualquier Gate.
        // Si devuelve true, el Gate se bypasea — acceso concedido.
        // Si devuelve null, el Gate normal se ejecuta.
        //
        // Esto permite que el super_admin haga todo sin
        // necesitar cada permiso individual.
        Gate::before(function (User $user) {
            if ($user->hasPermission('system:manage')) {
                return true; // super_admin: acceso total
            }
            return null; // Continuar con el Gate normal
        });
    }
}
