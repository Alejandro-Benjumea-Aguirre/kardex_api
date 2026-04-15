<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;

use App\Http\Requests\Products\{CreateProductsRequest, UpdateProductsRequest};
use App\Http\Resources\ProductsResource;
use App\Data\Products\{CreateProductsData, UpdateProductsData};
use App\Actions\Products\{
    CreateProductsAction, UpdateProductAction, DeactivateProductsAction, ActivateProductsAction
};
use App\Repositories\Interfaces\ProductsRepositoryExtendedInterface;
use App\Exceptions\Products\ProductsException;


class ProductsController extends Controller
{
    public function __construct(
        private readonly ProductsRepositoryExtendedInterface $productsRepository,
    ) {}

    // GET /products?search=&is_active=&category_id=&per_page=
    public function index(Request $request): JsonResponse
    {
        $products = $this->productsRepository->paginate(
            filters: [
                'company_id'  => $request->user()->company_id,
                'category_id' => $request->input('category_id'),
                'search'      => $request->input('search'),
                'is_active'   => $request->input('is_active'),
            ],
            perPage: (int) $request->input('per_page', 20),
        );

        return response()->json([
            'success' => true,
            'data'    => ProductsResource::collection($products),
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
            $product = $action(CreateProductsData::from($request));

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
    public function show(string $product): JsonResponse
    {
        $found = $this->productsRepository->findById($product);

        if (! $found) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'PRODUCT_NOT_FOUND', 'message' => 'Producto no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new ProductsResource($found),
        ]);
    }

    // GET /products/{category}/products
    public function byCategory(string $category): JsonResponse
    {
        $products = $this->productsRepository->findByCategory($category);

        return response()->json([
            'success' => true,
            'data'    => ProductsResource::collection($products),
        ]);
    }

    // PUT /products/{product}
    public function update(
        UpdateProductsRequest $request,
        string                $product,
        UpdateProductAction   $action,
    ): JsonResponse {
        $found = $this->productsRepository->findById($product);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'PRODUCT_NOT_FOUND']], 404);
        }

        try {
            $updated = $action($found, UpdateProductsData::from($request));

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado correctamente.',
                'data'    => new ProductsResource($updated),
            ]);

        } catch (ProductsException $e) {
            return $this->domainError($e);
        }
    }

    // DELETE /products/{product}
    public function destroy(
        string                  $product,
        DeactivateProductsAction $action,
    ): JsonResponse {
        $found = $this->productsRepository->findById($product);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'PRODUCT_NOT_FOUND']], 404);
        }

        try {
            $action($found);

            return response()->json([
                'success' => true,
                'message' => 'Producto desactivado correctamente.',
            ]);

        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'DOMAIN_ERROR', 'message' => $e->getMessage()],
            ], 422);
        }
    }

    // POST /products/{product}/activate
    public function activate(
        string                 $product,
        ActivateProductsAction $action,
    ): JsonResponse {
        $found = $this->productsRepository->findById($product);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'PRODUCT_NOT_FOUND']], 404);
        }

        $action($found);

        return response()->json([
            'success' => true,
            'message' => 'Producto activado correctamente.',
        ]);
    }

    private function domainError(ProductsException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error'   => ['code' => $e->getCode(), 'message' => $e->getMessage()],
        ], 422);
    }
}
