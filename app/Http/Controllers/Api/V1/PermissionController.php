<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\{CreateRoleRequest, UpdateRoleRequest, SyncPermissionsRequest};
use App\Http\Resources\{RoleResource, PermissionResource};
use App\Actions\Roles\{CreateRoleAction, UpdateRoleAction, DeleteRoleAction, SyncPermissionsAction};
use App\Repositories\Interfaces\{RoleRepositoryInterface, PermissionRepositoryInterface};
use App\Exceptions\Users\{UsersException, RoleNotFoundException};

// ═══════════════════════════════════════════════════════════
// PermissionController
//
// Los permisos del sistema son de solo lectura para la API.
// Solo el super_admin puede crear permisos custom.
//
// Endpoints:
//   GET /permissions             → todos los permisos disponibles
//   GET /permissions/by-module   → agrupados por módulo (para UI de editar rol)
// ═══════════════════════════════════════════════════════════

class PermissionController extends Controller
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissionRepository,
    ) {}

    // GET /permissions
    public function index(Request $request): JsonResponse
    {
        $permissions = $this->permissionRepository->allActive(
            $request->user()->company_id
        );

        return response()->json([
            'success' => true,
            'data'    => PermissionResource::collection($permissions),
        ]);
    }

    // GET /permissions/by-module
    // Devuelve los permisos agrupados — ideal para renderizar la UI
    // del editor de roles con checkboxes por módulo
    public function byModule(Request $request): JsonResponse
    {
        $grouped = $this->permissionRepository->allGroupedByModule(
            $request->user()->company_id
        );

        // Transformar a formato amigable para el frontend:
        // { "products": [{ id, name, display_name }, ...], "sales": [...] }
        $data = collect($grouped)->map(fn($perms) =>
            collect($perms)->map(fn($p) => [
                'id'           => $p['id'],
                'name'         => $p['name'],
                'display_name' => $p['display_name'],
                'description'  => $p['description'],
            ])->values()
        );

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
