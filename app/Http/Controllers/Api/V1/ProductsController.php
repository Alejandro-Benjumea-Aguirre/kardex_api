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
            $product = $action(CreateProductsData::from([
                ...$request->validated(),
                'company_id' => $request->user()->company_id,
            ]));

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

    // PATCH /products/{product}/activate
    public function changeStatus(
        Request                  $request,
        string                   $product,
        ActivateProductsAction   $activateAction,
        DeactivateProductsAction $deactivateAction,
    ): JsonResponse {
        $found = $this->productsRepository->findById($product);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'PRODUCT_NOT_FOUND']], 404);
        }

        $action = $request->input('action');

        if (! in_array($action, ['activate', 'deactivate'], true)) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'INVALID_ACTION', 'message' => 'La acción debe ser "activate" o "deactivate".'],
            ], 422);
        }

        if ($action === 'activate') {
            $activateAction($found);
            $message = 'Producto activado correctamente.';
        } else {
            $deactivateAction($found);
            $message = 'Producto desactivado correctamente.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
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
