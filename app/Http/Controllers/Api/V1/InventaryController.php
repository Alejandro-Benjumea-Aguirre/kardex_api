<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventaries\{
    CreateInventaryRequest, UpdateInventaryRequest
};
use App\Http\Resources\InventaryResource;
use App\Data\Inventaries\{CreateInventaryData, UpdateInventaryData};
use App\Actions\Inventaries\{
    CreateInventaryAction, UpdateInventaryAction, DeactivateInventaryAction, ActivateInventaryAction
};
use App\Repositories\Interfaces\InventaryRepositoryExtendedInterface;
use App\Exceptions\Inventaries\InventaryException;

class InventaryController extends Controller
{
    public function __construct(
        private readonly InventaryRepositoryExtendedInterface $inventaryRepository,
    ) {}

    // GET /inventary?search=&is_active=
    public function index(Request $request): JsonResponse
    {
        $inventaries = $this->inventaryRepository->paginate(
            filters: [
                'company_id' => $request->user()->company_id,
                'search'     => $request->input('search'),
                'is_active'  => $request->input('is_active'),
                'role_id'    => $request->input('role_id'),
            ],
            perPage: (int) $request->input('per_page', 20),
        );

        return response()->json([
            'success' => true,
            'data'    => InventaryResource::collection($inventaries),
            'meta'    => [
                'current_page' => $inventaries->currentPage(),
                'per_page'     => $inventaries->perPage(),
                'total'        => $inventaries->total(),
                'last_page'    => $inventaries->lastPage(),
            ],
        ]);
    }

    // POST /inventary
    public function store(CreateInventaryRequest $request, CreateInventaryAction $action): JsonResponse
    {
        try {
            $inventary = $action(CreateInventaryData::from($request));

            return response()->json([
                'success' => true,
                'message' => 'Inventario creado correctamente.',
                'data'    => new InventaryResource($inventary),
            ], 201);

        } catch (InventaryException $e) {
            return $this->domainError($e);
        }
    }

    // GET /inventary/{inventary}
    public function show(string $inventaryId): JsonResponse
    {
        $inventary = $this->inventaryRepository->findById($inventaryId);

        if (! $inventary) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'INVENTARY_NOT_FOUND', 'message' => 'Inventario no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new InventaryResource($inventary),
        ]);
    }

    // PUT /inventary/{inventary}
    public function update(
        UpdateInventaryRequest  $request,
        string                  $inventaryId,
        UpdateInventaryAction   $action,
    ): JsonResponse {
        $inventary = $this->inventaryRepository->findById($inventaryId);

        if (! $inventary) {
            return response()->json(['success' => false, 'error' => ['code' => 'INVENTARY_NOT_FOUND']], 404);
        }

        try {
            $updated = $action($inventary, UpdateInventaryData::from($request));

            return response()->json([
                'success' => true,
                'message' => 'Inventario actualizado correctamente.',
                'data'    => new InventaryResource($updated),
            ]);

        } catch (InventaryException $e) {
            return $this->domainError($e);
        }
    }

    // DELETE /inventary/{inventary}  → desactivar (soft)
    public function destroy(
        string                     $inventaryId,
        DeactivateInventaryAction $action,
        Request                    $request,
    ): JsonResponse {
        $inventary = $this->inventaryRepository->findById($inventaryId);

        if (! $inventary) {
            return response()->json(['success' => false, 'error' => ['code' => 'INVENTARY_NOT_FOUND']], 404);
        }

        try {
            $action($inventary);

            return response()->json([
                'success' => true,
                'message' => 'Inventario desactivado correctamente.',
            ]);

        } catch (InventaryException|\DomainException $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'DOMAIN_ERROR', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    // POST /inventary/{inventary}/activate
    public function activate(
        string                  $inventaryId,
        ActivateInventaryAction $action,
    ): JsonResponse {
        $inventary = $this->inventaryRepository->findById($inventaryId);

        if (! $inventary) {
            return response()->json(['success' => false, 'error' => ['code' => 'INVENTARY_NOT_FOUND']], 404);
        }

        $action($inventary);

        return response()->json([
            'success' => true,
            'message' => 'Inventario activado correctamente.',
        ]);
    }

    private function domainError(InventaryException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error'   => ['code' => $e->errorCode(), 'message' => $e->getMessage()],
        ], $e->httpStatus());
    }
}
