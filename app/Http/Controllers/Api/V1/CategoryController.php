<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;

use App\Http\Requests\Category\{
	CreateCategoryRequest, UpdateCategoryRequest
};
use App\Http\Resources\CategoryResource;
use App\Data\Category\{CreateCategoryData, UpdateCategoryData};
use App\Actions\Category\{
	CreateCategoryAction, UpdateCategoryAction, DeactivateCategoryAction, ActivateCategoryAction
};
use App\Repositories\Interfaces\CategoryRepositoryExtendedInterface;
use App\Exceptions\Category\CategoryException;


class CategoryController extends Controller
{
	public function __construct(
		private readonly CategoryRepositoryExtendedInterface $categoryRepository,
	) {}

	// GET /category?search=&is_active=&&per_page=
	public function index(Request $request): JsonResponse
	{
		$categories = $this->categoryRepository->paginate(
			filters: [
				'company_id' => $request->category()->company_id,
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
			$category = $action(CreateCategoryData::from($request), $request->category());

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

	public function subcategories(string $categoryId): JsonResponse
	{
		$category = $this->categoryRepository->findByParent($categoryId);

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

	// PUT /category/{category}
	public function update(
			UpdateCategoryRequest $request,
			string            $categoryId,
			UpdateCategoryAction  $action,
	): JsonResponse {
			$category = $this->categoryRepository->findById($categoryId);

			if (! $category) {
					return response()->json(['success' => false, 'error' => ['code' => 'CATEGORY_NOT_FOUND']], 404);
			}

			try {
					$updated = $action($category, UpdateCategoryData::from($request), $request->category());

					return response()->json([
							'success' => true,
							'message' => 'Categoria actualizada correctamente.',
							'data'    => new CategoryResource($updated),
					]);

			} catch (CategoryException $e) {
					return $this->domainError($e);
			}
	}

	// DELETE /category/{category}  → desactivar (soft)
	public function destroy(
		string               $categoryId,
		DeactivateCategoryAction $action,
		Request              $request,
	): JsonResponse {
		$category = $this->categoryRepository->findById($categoryId);

		if (! $category) {
			return response()->json(['success' => false, 'error' => ['code' => 'CATEGORY_NOT_FOUND']], 404);
		}

		try {
			$action($category, $request->category());

			return response()->json([
				'success' => true,
				'message' => 'Categoria desactivada correctamente.',
			]);

		} catch (CategoryException|\DomainException $e) {
			return response()->json([
				'success' => false,
				'error'   => ['code' => 'DOMAIN_ERROR', 'message' => $e->getMessage()],
			], 422);
		}
	}

    // POST /category/{category}/activate
    public function activate(
        string             $categoryId,
        ActivateCategoryAction $action,
    ): JsonResponse {
        $category = $this->categoryRepository->findById($categoryId);

        if (! $category) {
            return response()->json(['success' => false, 'error' => ['code' => 'CATEGORY_NOT_FOUND']], 404);
        }

        $action($category);

        return response()->json([
            'success' => true,
            'message' => 'Cateria activada correctamente.',
        ]);
    }

}
