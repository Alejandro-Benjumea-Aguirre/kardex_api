<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Http\Resources\BarcodeResource;
use App\Models\{Products, ProductVariant, Barcode};

class BarcodeController extends Controller
{
    // GET /products/{product}/barcode
    public function index(string $product): JsonResponse
    {
        $found = Products::find($product);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'PRODUCT_NOT_FOUND', 'message' => 'Producto no encontrado.']], 404);
        }

        $variantIds = ProductVariant::where('product_id', $product)->pluck('id');

        $barcodes = Barcode::with('variant')
            ->whereIn('product_variant_id', $variantIds)
            ->get();

        return response()->json([
            'success' => true,
            'data'    => BarcodeResource::collection($barcodes),
        ]);
    }

    // POST /products/{product}/barcode
    public function store(Request $request, string $product): JsonResponse
    {
        $found = Products::find($product);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'PRODUCT_NOT_FOUND', 'message' => 'Producto no encontrado.']], 404);
        }

        $validated = $request->validate([
            'product_variant_id' => ['required', 'uuid', 'exists:product_variants,id'],
            'code'               => ['required', 'string', 'max:100', 'unique:barcodes,code'],
            'type'               => ['nullable', 'string', 'in:ean13,ean8,upc,qr,custom'],
            'is_primary'         => ['nullable', 'boolean'],
        ]);

        // Verify the variant belongs to this product
        $variant = ProductVariant::where('product_id', $product)->find($validated['product_variant_id']);
        if (! $variant) {
            return response()->json(['success' => false, 'error' => ['code' => 'VARIANT_NOT_FOUND', 'message' => 'La variante no pertenece a este producto.']], 404);
        }

        $barcode = Barcode::create([
            ...$validated,
            'type'       => $validated['type']       ?? 'ean13',
            'is_primary' => $validated['is_primary'] ?? false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Código de barras creado correctamente.',
            'data'    => new BarcodeResource($barcode->load('variant')),
        ], 201);
    }

    // GET /products/{product}/barcode/{barcode}
    public function show(string $product, string $barcode): JsonResponse
    {
        $variantIds = ProductVariant::where('product_id', $product)->pluck('id');

        $found = Barcode::with('variant')
            ->whereIn('product_variant_id', $variantIds)
            ->find($barcode);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'BARCODE_NOT_FOUND', 'message' => 'Código de barras no encontrado.']], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new BarcodeResource($found),
        ]);
    }

    // PUT /products/{product}/barcode/{barcode}
    public function update(Request $request, string $product, string $barcode): JsonResponse
    {
        $variantIds = ProductVariant::where('product_id', $product)->pluck('id');

        $found = Barcode::whereIn('product_variant_id', $variantIds)->find($barcode);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'BARCODE_NOT_FOUND', 'message' => 'Código de barras no encontrado.']], 404);
        }

        $validated = $request->validate([
            'code'       => ['nullable', 'string', 'max:100', 'unique:barcodes,code,' . $barcode],
            'type'       => ['nullable', 'string', 'in:ean13,ean8,upc,qr,custom'],
            'is_primary' => ['nullable', 'boolean'],
        ]);

        $found->update(array_filter($validated, fn($v) => $v !== null));

        return response()->json([
            'success' => true,
            'message' => 'Código de barras actualizado correctamente.',
            'data'    => new BarcodeResource($found->fresh('variant')),
        ]);
    }

    // DELETE /products/{product}/barcode/{barcode}
    public function destroy(string $product, string $barcode): JsonResponse
    {
        $variantIds = ProductVariant::where('product_id', $product)->pluck('id');

        $found = Barcode::whereIn('product_variant_id', $variantIds)->find($barcode);

        if (! $found) {
            return response()->json(['success' => false, 'error' => ['code' => 'BARCODE_NOT_FOUND', 'message' => 'Código de barras no encontrado.']], 404);
        }

        $found->delete();

        return response()->json([
            'success' => true,
            'message' => 'Código de barras eliminado correctamente.',
        ]);
    }

    // GET /products/barcode/scan/{code}
    public function scan(string $code): JsonResponse
    {
        $barcode = Barcode::with(['variant.product'])
            ->where('code', $code)
            ->first();

        if (! $barcode) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'BARCODE_NOT_FOUND', 'message' => 'Código de barras no encontrado.'],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => new BarcodeResource($barcode),
        ]);
    }
}
