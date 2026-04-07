<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

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
