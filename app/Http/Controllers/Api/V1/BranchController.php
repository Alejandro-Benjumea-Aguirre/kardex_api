<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\{
    CreateBranchRequest, UpdateBranchRequest
};
use App\Http\Resources\BranchResource;
use App\Data\Users\{CreateBranchData, UpdateBranchData};
use App\Actions\Branch\{
    CreateBranchAction, UpdateBranchAction, DeactivateBranchAction, ActivateBranchAction
};
use App\Repositories\Interfaces\BranchRepositoryExtendedInterface;
use App\Exceptions\Branch\BranchException;

class BranchController extends Controller
{
    public function __construct(
        private readonly BranchRepositoryExtendedInterface $branchRepository,
    ) {}

    // GET /branch?search=&is_active=
    public function index(Request $request): JsonResponse
    {
        $branch = $this->branchRepository->paginate(
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
            'data'    => BranchResource::collection($branch),
            'meta'    => [
                'current_page' => $branch->currentPage(),
                'per_page'     => $branch->perPage(),
                'total'        => $branch->total(),
                'last_page'    => $branch->lastPage(),
            ],
        ]);
    }

    // POST /branch
    public function store(CreateBranchRequest $request, CreateBranchAction $action): JsonResponse
    {
        try {
            $branch = $action(CreateBranchData::from($request), $request->branch());

            return response()->json([
                'success' => true,
                'message' => 'Sucursal creada correctamente.',
                'data'    => new BranchResource($user),
            ], 201);

        } catch (BranchException $e) {
            return $this->domainError($e);
        }
    }

    // GET /branch/{branch}
    public function show(string $branchId): JsonResponse
    {
        $branch = $this->branchRepository->findById($branchId);

        if (! $branch) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'BRANCH_NOT_FOUND', 'message' => 'Sucursal no encontrada.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new BranchResource($branch),
        ]);
    }

    // PUT /branch/{branch}
    public function update(
        UpdateBranchRequest $request,
        string              $branchId,
        UpdateBranchAction  $action,
    ): JsonResponse {
        $branch = $this->branchRepository->findById($branchId);

        if (! $branch) {
            return response()->json(['success' => false, 'error' => ['code' => 'BRANCH_NOT_FOUND']], 404);
        }

        try {
            $updated = $action($branch, UpdateBranchData::from($request), $request->branch());

            return response()->json([
                'success' => true,
                'message' => 'Sucursal actualizada correctamente.',
                'data'    => new UserResource($updated),
            ]);

        } catch (BranchException $e) {
            return $this->domainError($e);
        }
    }

    // DELETE /branch/{branch}  → desactivar (soft)
    public function destroy(
        string                 $branchId,
        DeactivateBranchAction $action,
        Request                $request,
    ): JsonResponse {
        $branch = $this->branchRepository->findById($branchId);

        if (! $branch) {
            return response()->json(['success' => false, 'error' => ['code' => 'BRANCH_NOT_FOUND']], 404);
        }

        try {
            $action($branch, $request->branch());

            return response()->json([
                'success' => true,
                'message' => 'Sucursal desactivada correctamente.',
            ]);

        } catch (BranchException|\DomainException $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'DOMAIN_ERROR', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    // POST /branch/{branch}/activate
    public function activate(
        string               $branchId,
        ActivatebranchAction $action,
    ): JsonResponse {
        $branch = $this->branchRepository->findById($branchId);

        if (! $branch) {
            return response()->json(['success' => false, 'error' => ['code' => 'BRANCH_NOT_FOUND']], 404);
        }

        $action($branch);

        return response()->json([
            'success' => true,
            'message' => 'Sucursal activada correctamente.',
        ]);
    }

    private function domainError(BranchException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error'   => ['code' => $e->errorCode(), 'message' => $e->getMessage()],
        ], $e->httpStatus());
    }
}
