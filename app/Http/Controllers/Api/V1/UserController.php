<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\{
    CreateUserRequest, UpdateUserRequest, ChangePasswordRequest,
    AssignRoleRequest, SyncRolesRequest,
};
use App\Http\Resources\UserResource;
use App\Data\Users\{CreateUserData, UpdateUserData, ChangePasswordData, AssignRoleData};
use App\Actions\Users\{
    CreateUserAction, UpdateUserAction, ChangePasswordAction,
    DeactivateUserAction, ActivateUserAction,
    AssignRoleAction, RevokeRoleAction,
};
use App\Repositories\Interfaces\UserRepositoryExtendedInterface;
use App\Exceptions\Users\UsersException;

// ═══════════════════════════════════════════════════════════
// UserController
//
// Endpoints:
//   GET    /users            → index  (listar con filtros y paginación)
//   POST   /users            → store  (crear usuario)
//   GET    /users/{user}     → show   (detalle del usuario)
//   PUT    /users/{user}     → update (actualizar datos)
//   DELETE /users/{user}     → destroy (desactivar)
//   POST   /users/{user}/activate      → activar
//   PUT    /users/{user}/password      → cambiar password
//   POST   /users/{user}/roles         → asignar rol
//   DELETE /users/{user}/roles/{role}  → revocar rol
// ═══════════════════════════════════════════════════════════

class UserController extends Controller
{
    public function __construct(
        private readonly UserRepositoryExtendedInterface $userRepository,
    ) {}

    // GET /users?search=&is_active=&role_id=&per_page=
    public function index(Request $request): JsonResponse
    {
        $users = $this->userRepository->paginate(
            filters: [
                'company_id' => $request->user()->company_id,
                'search'     => $request->input('search'),
                'is_active'  => $request->input('is_active'),
                'role_id'    => $request->input('role_id'),
            ],
            perPage: (int) $request->input('per_page', 20),
        );

        // ─── CONCEPTO: ResourceCollection con paginación ─────
        //
        // UserResource::collection() convierte cada elemento
        // de la paginación en un UserResource.
        //
        // El resultado incluye automáticamente los metadatos
        // de paginación de Laravel:
        //   {
        //     "data": [...],
        //     "links": { "first": "...", "next": "...", ... },
        //     "meta":  { "current_page": 1, "total": 50, ... }
        //   }
        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($users),
            'meta'    => [
                'current_page' => $users->currentPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    // POST /users
    public function store(CreateUserRequest $request, CreateUserAction $action): JsonResponse
    {
        try {
            $user = $action(CreateUserData::from($request), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario creado correctamente.',
                'data'    => new UserResource($user),
            ], 201);

        } catch (UsersException $e) {
            return $this->domainError($e);
        }
    }

    // GET /users/{user}
    public function show(string $userId): JsonResponse
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'USER_NOT_FOUND', 'message' => 'Usuario no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new UserResource($user),
        ]);
    }

    // PUT /users/{user}
    public function update(
        UpdateUserRequest $request,
        string            $userId,
        UpdateUserAction  $action,
    ): JsonResponse {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            return response()->json(['success' => false, 'error' => ['code' => 'USER_NOT_FOUND']], 404);
        }

        try {
            $updated = $action($user, UpdateUserData::from($request), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario actualizado correctamente.',
                'data'    => new UserResource($updated),
            ]);

        } catch (UsersException $e) {
            return $this->domainError($e);
        }
    }

    // DELETE /users/{user}  → desactivar (soft)
    public function destroy(
        string               $userId,
        DeactivateUserAction $action,
        Request              $request,
    ): JsonResponse {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            return response()->json(['success' => false, 'error' => ['code' => 'USER_NOT_FOUND']], 404);
        }

        try {
            $action($user, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Usuario desactivado correctamente.',
            ]);

        } catch (UsersException|\DomainException $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'DOMAIN_ERROR', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    // POST /users/{user}/activate
    public function activate(
        string             $userId,
        ActivateUserAction $action,
    ): JsonResponse {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            return response()->json(['success' => false, 'error' => ['code' => 'USER_NOT_FOUND']], 404);
        }

        $action($user);

        return response()->json([
            'success' => true,
            'message' => 'Usuario activado correctamente.',
        ]);
    }

    // PUT /users/{user}/password
    public function changePassword(
        ChangePasswordRequest $request,
        ChangePasswordAction  $action,
    ): JsonResponse {
        try {
            $action($request->user(), ChangePasswordData::from($request));

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada. Las demás sesiones han sido cerradas.',
            ]);

        } catch (\App\Exceptions\Auth\InvalidCredentialsException $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'WRONG_PASSWORD', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    // POST /users/{user}/roles
    public function assignRole(
        AssignRoleRequest $request,
        string            $userId,
        AssignRoleAction  $action,
    ): JsonResponse {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            return response()->json(['success' => false, 'error' => ['code' => 'USER_NOT_FOUND']], 404);
        }

        try {
            $action($user, AssignRoleData::from($request), $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Rol asignado correctamente.',
                'data'    => new UserResource($this->userRepository->findById($userId)),
            ]);

        } catch (UsersException|\DomainException $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => $e instanceof UsersException ? $e->errorCode() : 'DOMAIN_ERROR', 'message' => $e->getMessage()],
            ], $e instanceof UsersException ? $e->httpStatus() : 422);
        }
    }

    // DELETE /users/{user}/roles/{role}?branch_id=
    public function revokeRole(
        Request          $request,
        string           $userId,
        string           $roleId,
        RevokeRoleAction $action,
    ): JsonResponse {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            return response()->json(['success' => false, 'error' => ['code' => 'USER_NOT_FOUND']], 404);
        }

        try {
            $action($user, $roleId, $request->input('branch_id'));

            return response()->json([
                'success' => true,
                'message' => 'Rol revocado correctamente.',
            ]);

        } catch (UsersException|\DomainException $e) {
            return response()->json([
                'success' => false,
                'error'   => ['message' => $e->getMessage()],
            ], 422);
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
