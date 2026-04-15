<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductVariantResource;
use App\Models\{Products, ProductVariant};

class ProductVariantController extends Controller
{
    // GET /products/{product}/variant
    public function index(string $product): JsonResponse
    {
        $found = Products::find($product);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'PRODUCT_NOT_FOUND', 'message' => 'Producto no encontrado.']], 404);
        }

        $variants = ProductVariant::with('barcodes')
            ->where('product_id', $product)
            ->orderBy('sort_order')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => ProductVariantResource::collection($variants),
        ]);
    }

    // POST /products/{product}/variant
    public function store(Request $request, string $product): JsonResponse
    {
        $found = Products::find($product);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'PRODUCT_NOT_FOUND', 'message' => 'Producto no encontrado.']], 404);
        }

        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:200'],
            'sku'        => ['nullable', 'string', 'max:100', 'unique:product_variants,sku'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'attributes' => ['nullable', 'array'],
            'image_url'  => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $variant = ProductVariant::create([
            ...$validated,
            'product_id' => $product,
            'is_active'  => $validated['is_active']  ?? true,
            'is_default' => $validated['is_default'] ?? false,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Variante creada correctamente.',
            'data'    => new ProductVariantResource($variant),
        ], 201);
    }

    // GET /products/{product}/variant/{variant}
    public function show(string $product, string $variant): JsonResponse
    {
        $found = ProductVariant::with('barcodes')
            ->where('product_id', $product)
            ->find($variant);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'VARIANT_NOT_FOUND', 'message' => 'Variante no encontrada.']], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new ProductVariantResource($found),
        ]);
    }

    // PUT /products/{product}/variant/{variant}
    public function update(Request $request, string $product, string $variant): JsonResponse
    {
        $found = ProductVariant::where('product_id', $product)->find($variant);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'VARIANT_NOT_FOUND', 'message' => 'Variante no encontrada.']], 404);
        }

        $validated = $request->validate([
            'name'       => ['nullable', 'string', 'max:200'],
            'sku'        => ['nullable', 'string', 'max:100', 'unique:product_variants,sku,' . $variant],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'attributes' => ['nullable', 'array'],
            'image_url'  => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $found->update(array_filter($validated, fn($v) => $v !== null));

        return response()->json([
            'success' => true,
            'message' => 'Variante actualizada correctamente.',
            'data'    => new ProductVariantResource($found->fresh()),
        ]);
    }

    // DELETE /products/{product}/variant/{variant}
    public function destroy(string $product, string $variant): JsonResponse
    {
        $found = ProductVariant::where('product_id', $product)->find($variant);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'VARIANT_NOT_FOUND', 'message' => 'Variante no encontrada.']], 404);
        }

        $found->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Variante desactivada correctamente.',
        ]);
    }

    // POST /products/{product}/variant/{variant}/activate
    public function activate(string $product, string $variant): JsonResponse
    {
        $found = ProductVariant::where('product_id', $product)->find($variant);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'VARIANT_NOT_FOUND', 'message' => 'Variante no encontrada.']], 404);
        }

        $found->update(['is_active' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Variante activada correctamente.',
        ]);
    }
}
