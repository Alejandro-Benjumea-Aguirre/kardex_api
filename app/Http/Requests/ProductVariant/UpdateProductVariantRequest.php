<?php

declare(strict_types=1);

namespace App\Http\Requests\ProductVariants;

use Illuminate\Validation\Rule;

class UpdateProductVariantRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        $variantId = $this->route('variant');

        return [
            'name'       => ['nullable', 'string', 'max:200'],
            'sku'        => ['nullable', 'string', 'max:100',
                                Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'attributes' => ['nullable', 'array'],
            'image_url'  => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique'         => 'Este SKU ya está en uso por otra variante.',
            'cost_price.numeric' => 'El precio de costo debe ser un valor numérico.',
            'sale_price.numeric' => 'El precio de venta debe ser un valor numérico.',
            'image_url.url'      => 'La URL de la imagen no es válida.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
        ];
    }
}