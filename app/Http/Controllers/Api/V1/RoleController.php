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
// RoleController
//
// Endpoints:
//   GET    /roles             → index  (listar roles de la empresa)
//   POST   /roles             → store  (crear rol custom)
//   GET    /roles/{role}      → show   (detalle con permisos)
//   PUT    /roles/{role}      → update (actualizar datos del rol)
//   DELETE /roles/{role}      → destroy (eliminar rol)
//   PUT    /roles/{role}/permissions → syncPermissions
// ═══════════════════════════════════════════════════════════

class RoleController extends Controller
{
    public function __construct(
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    // GET /roles
    public function index(Request $request): JsonResponse
    {
        $roles = $this->roleRepository
            ->allForCompany($request->user()->company_id)
            ->load('permissions');

        return response()->json([
            'success' => true,
            'data'    => RoleResource::collection($roles),
        ]);
    }

    // POST /roles
    public function store(CreateRoleRequest $request, CreateRoleAction $action): JsonResponse
    {
        try {
            $role = $action($request->validated(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Rol creado correctamente.',
                'data'    => new RoleResource($role),
            ], 201);

        } catch (UsersException $e) {
            return $this->domainError($e);
        }
    }

    // GET /roles/{role}
    public function show(string $roleId): JsonResponse
    {
        $role = $this->roleRepository->findById($roleId);

        if (! $role) {
            return response()->json(['success' => false, 'error' => ['code' => 'ROLE_NOT_FOUND']], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new RoleResource($role->load('permissions')),
        ]);
    }

    // PUT /roles/{role}
    public function update(
        UpdateRoleRequest $request,
        string            $roleId,
        UpdateRoleAction  $action,
    ): JsonResponse {
        $role = $this->roleRepository->findById($roleId);

        if (! $role) {
            return response()->json(['success' => false, 'error' => ['code' => 'ROLE_NOT_FOUND']], 404);
        }

        try {
            $updated = $action($role, $request->validated(), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Rol actualizado correctamente.',
                'data'    => new RoleResource($updated),
            ]);

        } catch (UsersException $e) {
            return $this->domainError($e);
        }
    }

    // DELETE /roles/{role}
    public function destroy(
        string           $roleId,
        DeleteRoleAction $action,
        Request          $request,
    ): JsonResponse {
        $role = $this->roleRepository->findById($roleId);

        if (! $role) {
            return response()->json(['success' => false, 'error' => ['code' => 'ROLE_NOT_FOUND']], 404);
        }

        try {
            $action($role, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Rol eliminado correctamente.',
            ]);

        } catch (UsersException|\DomainException $e) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => $e->getMessage()],
            ], 422);
        }
    }

    // PUT /roles/{role}/permissions
    public function syncPermissions(
        SyncPermissionsRequest $request,
        string                 $roleId,
        SyncPermissionsAction  $action,
    ): JsonResponse {
        $role = $this->roleRepository->findById($roleId);

        if (! $role) {
            return response()->json(['success' => false, 'error' => ['code' => 'ROLE_NOT_FOUND']], 404);
        }

        try {
            $updated = $action($role, $request->permission_ids, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Permisos del rol actualizados.',
                'data'    => new RoleResource($updated),
            ]);

        } catch (UsersException $e) {
            return $this->domainError($e);
        }
    }

    private function domainError(UsersException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error'   => ['code' => $e->errorCode(), 'message' => $e->getMessage()],
        ], $e->httpStatus());
    }
}
