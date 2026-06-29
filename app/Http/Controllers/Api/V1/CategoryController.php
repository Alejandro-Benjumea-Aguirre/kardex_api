<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;

use App\Http\Requests\Categories\{
    CreateCategoryRequest, UpdateCategoryRequest
};
use App\Http\Resources\CategoryResource;
use App\Data\Categories\{CreateCategoryData, UpdateCategoryData};
use App\Actions\Categories\{
    CreateCategoryAction, UpdateCategoryAction, DeactivateCategoryAction, ActivateCategoryAction
};
use App\Repositories\Interfaces\CategoryRepositoryExtendedInterface;
use App\Exceptions\Categories\CategoryException;


class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryRepositoryExtendedInterface $categoryRepository,
    ) {}

    // GET /category?search=&is_active=&per_page=
    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryRepository->paginate(
            filters: [
                'company_id' => $request->user()->company_id,
                'search'     => $request->input('search'),
                'is_active'  => $request->input('is_active'),
            ],
            perPage: (int) $request->input('per_page', 20),
        );

        return response()->json([
            'success' => true,
            'data'    => CategoryResource::collection($categories),
            'meta'    => [
                'current_page' => $categories->currentPage(),
                'per_page'     => $categories->perPage(),
                'total'        => $categories->total(),
                'last_page'    => $categories->lastPage(),
            ],
        ]);
    }

    // POST /category
    public function store(CreateCategoryRequest $request, CreateCategoryAction $action): JsonResponse
    {
        try {
            $category = $action(CreateCategoryData::from([
                ...$request->validated(),
                'company_id' => $request->user()->company_id,
            ]));

            return response()->json([
                'success' => true,
                'message' => 'Categoria creada correctamente.',
                'data'    => new CategoryResource($category),
            ], 201);

        } catch (CategoryException $e) {
            return $this->domainError($e);
        }
    }

    // GET /category/{category}
    public function show(string $categoryId): JsonResponse
    {
        $category = $this->categoryRepository->findById($categoryId);

        if (! $category) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'CATEGORY_NOT_FOUND', 'message' => 'Categoria no encontrada.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new CategoryResource($category),
        ]);
    }

    // GET /category/{category}/subcategories
    public function subcategories(string $categoryId): JsonResponse
    {
        $subcategories = $this->categoryRepository->findByParent($categoryId);

        return response()->json([
            'success' => true,
            'data'    => CategoryResource::collection($subcategories),
        ]);
    }

    // PUT /category/{category}
    public function update(
        UpdateCategoryRequest $request,
        string                $categoryId,
        UpdateCategoryAction  $action,
    ): JsonResponse {
        $category = $this->categoryRepository->findById($categoryId);

        if (! $category) {
            return response()->json(['success' => false, 'error' => ['code' => 'CATEGORY_NOT_FOUND']], 404);
        }

        try {
            $updated = $action($category, UpdateCategoryData::from($request));

            return response()->json([
                'success' => true,
                'message' => 'Categoria actualizada correctamente.',
                'data'    => new CategoryResource($updated),
            ]);

        } catch (CategoryException $e) {
            return $this->domainError($e);
        }
    }

    // PATCH /category/{category}/activate
    public function changeStatus(
        Request                  $request,
        string                   $categoryId,
        ActivateCategoryAction   $activateAction,
        DeactivateCategoryAction $deactivateAction,
    ): JsonResponse {
        $category = $this->categoryRepository->findById($categoryId);

        if (! $category) {
            return response()->json(['success' => false, 'error' => ['code' => 'CATEGORY_NOT_FOUND']], 404);
        }

        $action = $request->input('action');

        if (! in_array($action, ['activate', 'deactivate'], true)) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'INVALID_ACTION', 'message' => 'La acción debe ser "activate" o "deactivate".'],
            ], 422);
        }

        if ($action === 'activate') {
            $activateAction($category);
            $message = 'Categoria activada correctamente.';
        } else {
            $deactivateAction($category);
            $message = 'Categoria desactivada correctamente.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    private function domainError(CategoryException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error'   => ['code' => $e->getCode(), 'message' => $e->getMessage()],
        ], 422);
    }
}
