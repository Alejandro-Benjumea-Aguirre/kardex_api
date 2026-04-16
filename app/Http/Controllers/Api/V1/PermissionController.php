<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Http\Resources\PermissionResource;
use App\Repositories\Interfaces\PermissionRepositoryInterface;


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
    // Devuelve los permisos agrupados
    public function byModule(Request $request): JsonResponse
    {
        $grouped = $this->permissionRepository->allGroupedByModule(
            $request->user()->company_id
        );

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
