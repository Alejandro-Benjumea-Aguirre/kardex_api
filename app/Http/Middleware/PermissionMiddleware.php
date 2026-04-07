<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// ═══════════════════════════════════════════════════════════
// PermissionMiddleware
//
// CONCEPTO: Autorización a nivel de ruta
// ═══════════════════════════════════════════════════════════
//
// JwtAuthMiddleware verifica: "¿Estás autenticado?" (AuthN)
// PermissionMiddleware verifica: "¿Tenés permiso para esto?" (AuthZ)
//
// DIFERENCIA IMPORTANTE:
//   Autenticación (AuthN): ¿Quién sos?
//   Autorización  (AuthZ): ¿Qué podés hacer?
//
// USO EN RUTAS:
//   Route::middleware(['auth.jwt', 'permission:products:create'])
//        ->post('/products', ...)
//
// Múltiples permisos con OR (cualquiera de los dos):
//   Route::middleware('permission:products:create|products:update')
//
// Múltiples permisos con AND (todos requeridos):
//   Route::middleware(['permission:products:create', 'permission:products:update'])
//
// REGISTRO EN bootstrap/app.php:
//   $middleware->alias(['permission' => PermissionMiddleware::class]);
// ═══════════════════════════════════════════════════════════

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user     = $request->user();
        $branchId = $request->attributes->get('current_branch')
                 ?? $request->input('branch_id');

        // Super admin con permiso system:manage pasa todo
        if ($user->hasPermission('system:manage')) {
            return $next($request);
        }

        // ─── Verificar permisos requeridos ────────────────
        //
        // $permissions puede venir como:
        //   ['products:create']             → AND: requiere ese permiso
        //   ['products:create|sales:create'] → OR: requiere uno de los dos
        //
        // Cada elemento del array se considera AND.
        // Dentro de cada elemento, | separa alternativas OR.
        foreach ($permissions as $permissionGroup) {
            $alternatives = explode('|', $permissionGroup);

            // ¿El usuario tiene AL MENOS UNA alternativa?
            $hasAny = $user->hasAnyPermission($alternatives, $branchId);

            if (! $hasAny) {
                return response()->json([
                    'success' => false,
                    'error'   => [
                        'code'    => 'INSUFFICIENT_PERMISSIONS',
                        'message' => 'No tenés permisos para realizar esta acción.',
                        'required' => $permissionGroup,
                    ],
                ], 403);
            }
        }

        return $next($request);
    }
}
