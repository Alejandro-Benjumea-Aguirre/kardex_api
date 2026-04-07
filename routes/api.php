<?php

// ═══════════════════════════════════════════════════════════
// routes/api.php — Rutas del módulo Auth
//
// CONCEPTO: Versionado de API (/v1/)
// ═══════════════════════════════════════════════════════════
//
// Todas las rutas llevan el prefijo /api/v1/.
// ¿Por qué v1? Porque en el futuro podés tener v2 con cambios
// que rompen la API actual, y los clientes que usan v1
// siguen funcionando sin tocar su código.
//
// ESTRUCTURA DE RUTAS:
//
//   Públicas (sin autenticación):
//     POST   /api/v1/auth/login
//     POST   /api/v1/auth/register
//     POST   /api/v1/auth/refresh
//     POST   /api/v1/auth/forgot-password
//     POST   /api/v1/auth/reset-password
//     GET    /api/v1/auth/verify-email/{id}/{token}
//
//   Protegidas (requieren JWT válido):
//     POST   /api/v1/auth/logout
//     POST   /api/v1/auth/logout-all
//     GET    /api/v1/auth/me
// ═══════════════════════════════════════════════════════════

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\{
    AuthController,
    UserController,
    RoleController,
    PermissionController,
};

Route::prefix('v1')->group(function () {

    // ══════════════════════════════════════════════════════
    // AUTH
    // ══════════════════════════════════════════════════════

    Route::prefix('auth')->name('auth.')->group(function () {

        // ── PÚBLICAS (sin middleware de autenticación) ───

        // Throttle: rate limiting para prevenir brute force
        // '5,1' → máximo 5 intentos por minuto por IP
        // Laravel guarda los intentos en Redis (si configurado) o caché
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('login', [AuthController::class, 'login'])
                 ->name('login');
        });

        Route::post('register', [AuthController::class, 'register'])
             ->name('register');

        Route::post('refresh', [AuthController::class, 'refresh'])
             ->name('refresh');

        // Throttle más permisivo para forgot-password
        // (los usuarios legítimos olvidan su contraseña más de 5 veces)
        Route::middleware('throttle:3,1')->group(function () {
            Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
                 ->name('forgot-password');
        });

        Route::post('reset-password', [AuthController::class, 'resetPassword'])
             ->name('reset-password');

        // Verificación de email — GET porque viene de un link en el email
        Route::get('verify-email/{id}/{token}', [AuthController::class, 'verifyEmail'])
             ->name('verify-email');

        // ── PROTEGIDAS (requieren JWT válido) ────────────

        Route::middleware('auth.jwt')->group(function () {
            Route::post('logout',     [AuthController::class, 'logout'])
                 ->name('logout');

            Route::post('logout-all', [AuthController::class, 'logoutAll'])
                 ->name('logout-all');

            Route::get('me',          [AuthController::class, 'me'])
                 ->name('me');
        });
    });

    // ══════════════════════════════════════════════════════
    // USERS
    // ══════════════════════════════════════════════════════

     Route::middleware('auth.jwt')->prefix('users')->name('users.')->group(function () {

        // Listar usuarios de la empresa (con filtros y paginación)
        Route::get('/', [UserController::class, 'index'])
             ->middleware('permission:users:read')
             ->name('index');

        // Crear nuevo usuario
        Route::post('/', [UserController::class, 'store'])
             ->middleware('permission:users:create')
             ->name('store');

        // Ver perfil propio — sin permiso especial, solo JWT
        // Está en AuthController (GET /auth/me)

        // Endpoints que operan sobre un usuario específico
        Route::prefix('{user}')->group(function () {

            // Ver detalle de un usuario
            // Permite: admin ver cualquiera, o usuario ver a sí mismo
            Route::get('/', [UserController::class, 'show'])
                 ->middleware('permission:users:read')
                 ->name('show');

            // Actualizar datos del usuario
            Route::put('/', [UserController::class, 'update'])
                 ->middleware('permission:users:update')
                 ->name('update');

            // Desactivar usuario (soft — no borra)
            Route::delete('/', [UserController::class, 'destroy'])
                 ->middleware('permission:users:delete')
                 ->name('destroy');

            // Reactivar usuario
            Route::post('/activate', [UserController::class, 'activate'])
                 ->middleware('permission:users:update')
                 ->name('activate');

            // Cambiar contraseña del propio usuario (sin permiso especial)
            // El usuario solo puede cambiar SU PROPIA contraseña.
            // La validación del "usuario actual = usuario del token" va en la Action.
            Route::put('/password', [UserController::class, 'changePassword'])
                 ->name('change-password');

            // ── GESTIÓN DE ROLES DEL USUARIO ──────────────

            // Asignar un rol al usuario
            Route::post('/roles', [UserController::class, 'assignRole'])
                 ->middleware('permission:users:assign-roles')
                 ->name('roles.assign');

            // Revocar un rol específico del usuario
            // ?branch_id= para especificar el scope (opcional)
            Route::delete('/roles/{role}', [UserController::class, 'revokeRole'])
                 ->middleware('permission:users:assign-roles')
                 ->name('roles.revoke');
        });
    });

    // ══════════════════════════════════════════════════════
    // ROLES
    // ══════════════════════════════════════════════════════

    Route::middleware('auth.jwt')->prefix('roles')->name('roles.')->group(function () {

        // Listar todos los roles de la empresa (+ globales)
        Route::get('/', [RoleController::class, 'index'])
             ->middleware('permission:roles:read')
             ->name('index');

        // Crear rol personalizado
        Route::post('/', [RoleController::class, 'store'])
             ->middleware('permission:roles:create')
             ->name('store');

        Route::prefix('{role}')->group(function () {

            // Ver detalle del rol con todos sus permisos
            Route::get('/', [RoleController::class, 'show'])
                 ->middleware('permission:roles:read')
                 ->name('show');

            // Actualizar nombre/descripción del rol
            Route::put('/', [RoleController::class, 'update'])
                 ->middleware('permission:roles:update')
                 ->name('update');

            // Eliminar rol (solo si no tiene usuarios asignados)
            Route::delete('/', [RoleController::class, 'destroy'])
                 ->middleware('permission:roles:delete')
                 ->name('destroy');

            // Sincronizar permisos del rol
            // Envía el array COMPLETO de IDs de permisos que debe tener.
            // Los que faltan se agregan, los que sobran se quitan.
            // PUT porque reemplaza el estado completo (no es partial).
            Route::put('/permissions', [RoleController::class, 'syncPermissions'])
                 ->middleware('permission:roles:update')
                 ->name('permissions.sync');
        });
    });

    // ══════════════════════════════════════════════════════
    // PERMISOS
    // ══════════════════════════════════════════════════════

    Route::middleware('auth.jwt')->prefix('permissions')->name('permissions.')->group(function () {

        // Listar todos los permisos disponibles
        Route::get('/', [PermissionController::class, 'index'])
             ->middleware('permission:roles:read')
             ->name('index');

        // Permisos agrupados por módulo
        // Útil para la UI del editor de roles (checkboxes agrupados)
        Route::get('/by-module', [PermissionController::class, 'byModule'])
             ->middleware('permission:roles:read')
             ->name('by-module');
    });
});
