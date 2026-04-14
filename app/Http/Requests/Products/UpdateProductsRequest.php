<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use Illuminate\Validation\Rule;

class UpdateProductsRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        // Obtiene el ID del producto desde la ruta
        // Ej: /api/product/{product}
        $productId = $this->route('product');

        return [
            'company_id'         => ['nullable', 'uuid', 'exists:companies,id'],
            'category_id'        => ['nullable', 'uuid', 'exists:categories,id'],

            // ── Identificación ──────────────────────────────
            'name'               => ['nullable', 'string', 'max:200'],
            'slug'               => ['nullable', 'string', 'max:200',
                                        Rule::unique('products', 'slug')->ignore($productId)],
            'sku'                => ['nullable', 'string', 'max:100',
                                        Rule::unique('products', 'sku')->ignore($productId)],
            'description'        => ['nullable', 'string', 'max:500'],

            // ── Precios ─────────────────────────────────────
            'cost_price'         => ['nullable', 'numeric', 'min:0'],
            'sale_price'         => ['nullable', 'numeric', 'min:0'],
            'min_price'          => ['nullable', 'numeric', 'min:0'],

            // ── Impuestos ────────────────────────────────────
            'price_includes_tax' => ['nullable', 'boolean'],
            'tax_rate'           => ['nullable', 'numeric', 'min:0', 'max:100'],

            // ── Tipo y variantes ─────────────────────────────
            'type'               => ['nullable', 'string', Rule::in([
                                        'dish',
                                        'beverage',
                                        'dessert',
                                        'other',
                                    ])],
            'has_variants'       => ['nullable', 'boolean'],

            // ── Media y atributos ────────────────────────────
            'images'             => ['nullable', 'array'],
            'images.*'           => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'attributes'         => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            // company
            'company_id.uuid'            => 'El ID de la compañía no es válido.',
            'company_id.exists'          => 'La compañía no existe.',

            // category
            'category_id.uuid'           => 'El ID de la categoría no es válido.',
            'category_id.exists'         => 'La categoría no existe.',

            // name
            'name.max'                   => 'El nombre no puede superar los 200 caracteres.',

            // slug
            'slug.unique'                => 'Este slug ya está en uso por otro producto.',
            'slug.max'                   => 'El slug no puede superar los 200 caracteres.',

            // sku
            'sku.unique'                 => 'Este SKU ya está en uso por otro producto.',
            'sku.max'                    => 'El SKU no puede superar los 100 caracteres.',

            // description
            'description.max'            => 'La descripción no puede superar los 500 caracteres.',

            // precios
            'cost_price.numeric'         => 'El precio de costo debe ser un valor numérico.',
            'cost_price.min'             => 'El precio de costo no puede ser negativo.',

            'sale_price.numeric'         => 'El precio de venta debe ser un valor numérico.',
            'sale_price.min'             => 'El precio de venta no puede ser negativo.',

            'min_price.numeric'          => 'El precio mínimo debe ser un valor numérico.',
            'min_price.min'              => 'El precio mínimo no puede ser negativo.',

            // impuestos
            'tax_rate.numeric'           => 'La tasa de impuesto debe ser un valor numérico.',
            'tax_rate.min'               => 'La tasa de impuesto no puede ser negativa.',
            'tax_rate.max'               => 'La tasa de impuesto no puede superar el 100%.',

            // type
            'type.in'                    => 'El tipo debe ser: dish, beverage, dessert u other.',

            // images
            'images.array'               => 'Las imágenes deben enviarse como un arreglo.',
            'images.*.image'             => 'Cada archivo debe ser una imagen válida.',
            'images.*.mimes'             => 'Las imágenes deben ser jpg, jpeg, png o webp.',
            'images.*.max'               => 'Cada imagen no puede superar los 2MB.',
        ];
    }
}