<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;

use App\Http\Requests\Products\{
	CreateProductsRequest, UpdateProductsRequest
};
use App\Http\Resources\ProductsResource;
use App\Data\Products\{CreateProductsData, UpdateProductsData};
use App\Actions\Products\{
	CreateProductsAction, UpdateProductsAction, DeactivateProductsAction, ActivateProductsAction
};
use App\Repositories\Interfaces\ProductsRepositoryExtendedInterface;
use App\Exceptions\Products\ProductsException;


class ProductsController extends Controller
{
	public function __construct(
		private readonly ProductsRepositoryExtendedInterface $productsRepository,
	) {}

	// GET /products?search=&is_active=&&per_page=
	public function index(Request $request): JsonResponse
	{
		$products = $this->productsRepository->paginate(
			filters: [
				'company_id' => $request->category()->company_id,
				'search'     => $request->input('search'),
				'is_active'  => $request->input('is_active'),
			],
			perPage: (int) $request->input('per_page', 20),
		);

		return response()->json([
			'success' => true,
			'data'    => CategoryResource::collection($products),
			'meta'    => [
				'current_page' => $products->currentPage(),
				'per_page'     => $products->perPage(),
				'total'        => $products->total(),
				'last_page'    => $products->lastPage(),
			],
		]);
	}

	// POST /products
	public function store(CreateProductsRequest $request, CreateProductsAction $action): JsonResponse
	{
		try {
			$product = $action(CreateProductsData::from($request), $request->product());

			return response()->json([
				'success' => true,
				'message' => 'Producto creado correctamente.',
				'data'    => new ProductsResource($product),
			], 201);

		} catch (ProductsException $e) {
			return $this->domainError($e);
		}
	}

	// GET /products/{product}
	public function show(string $productId): JsonResponse
	{
		$product = $this->productsRepository->findById($productId);

		if (! $product) {
			return response()->json([
				'success' => false,
				'error'   => ['code' => 'PRODUCT_NOT_FOUND', 'message' => 'Producto no encontrado.'],
			], 404);
		}

		return response()->json([
			'success' => true,
			'data'    => new ProductResource($product),
		]);
	}

	public function byCategory(string $categoryId): JsonResponse
	{
		$products = $this->productRepository->findByCategory($categoryId);

		if (! $products) {
			return response()->json([
				'success' => false,
				'error'   => ['code' => 'PRODUCT_NOT_FOUND', 'message' => 'Productos no encontrados.'],
			], 404);
		}

		return response()->json([
			'success' => true,
			'data'    => new ProductsResource($products),
		]);
	}

	// PUT /products/{product}
	public function update(
			UpdateCategoryRequest $request,
			string            $productId,
			UpdateProductAction  $action,
	): JsonResponse {
			$product = $this->productRepository->findById($productId);

			if (! $product) {
					return response()->json(['success' => false, 'error' => ['code' => 'PRODUCT_NOT_FOUND']], 404);
			}

			try {
                $updated = $action($product, UpdateProductData::from($request), $request->product());

                return response()->json([
                        'success' => true,
                        'message' => 'Producto actualizado correctamente.',
                        'data'    => new ProductResource($updated),
                ]);

			} catch (ProductException $e) {
					return $this->domainError($e);
			}
	}

	// DELETE /product/{product}  → desactivar (soft)
	public function destroy(
		string               $productId,
		DeactivateProductAction $action,
		Request              $request,
	): JsonResponse {
		$product = $this->productRepository->findById($productId);

		if (! $product) {
			return response()->json(['success' => false, 'error' => ['code' => 'PRODUCT_NOT_FOUND']], 404);
		}

		try {
			$action($product, $request->category());

			return response()->json([
				'success' => true,
				'message' => 'Producto desactivado correctamente.',
			]);

		} catch (ProductException|\DomainException $e) {
			return response()->json([
				'success' => false,
				'error'   => ['code' => 'DOMAIN_ERROR', 'message' => $e->getMessage()],
			], 422);
		}
	}

    // POST /product/{product}/activate
    public function activate(
        string             $productId,
        ActivateProductAction $action,
    ): JsonResponse {
        $product = $this->productRepository->findById($productId);

        if (! $product) {
            return response()->json(['success' => false, 'error' => ['code' => 'PRODUCT_NOT_FOUND']], 404);
        }

        $action($product);

        return response()->json([
            'success' => true,
            'message' => 'Producto activado correctamente.',
        ]);
    }

}
