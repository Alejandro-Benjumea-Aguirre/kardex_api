<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Validation\Rule;

class CreateStockMovementRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            // ─── RELACIONES ───────────────────────────────
            'branch_id'          => ['required', 'uuid', 'exists:branches,id'],
            'product_variant_id' => ['required', 'uuid', 'exists:product_variants,id'],

            // ─── TIPO ─────────────────────────────────────
            'type'               => ['required', Rule::in([
                                        'purchase',
                                        'sale',
                                        'sale_return',
                                        'purchase_return',
                                        'adjustment_in',
                                        'adjustment_out',
                                        'transfer_in',
                                        'transfer_out',
                                        'waste',
                                        'initial',
                                    ])],

            // ─── CANTIDAD ─────────────────────────────────
            'quantity'           => ['required', 'numeric', 'min:0.001'],

            // ─── COSTO ────────────────────────────────────
            'unit_cost'          => ['nullable', 'numeric', 'min:0'],

            // ─── REFERENCIA ───────────────────────────────
            'reference_type'     => ['nullable', 'string', 'max:50',
                                        Rule::in(['sale', 'purchase', 'transfer', 'adjustment'])],
            'reference_id'       => ['nullable', 'uuid',
                                        'required_with:reference_type'],

            // ─── NOTAS ────────────────────────────────────
            'notes'              => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            // relaciones
            'branch_id.required'          => 'La sucursal es obligatoria.',
            'branch_id.exists'            => 'La sucursal no existe.',

            'product_variant_id.required' => 'La variante del producto es obligatoria.',
            'product_variant_id.exists'   => 'La variante del producto no existe.',

            // tipo
            'type.required'               => 'El tipo de movimiento es obligatorio.',
            'type.in'                     => 'El tipo de movimiento no es válido.',

            // cantidad
            'quantity.required'           => 'La cantidad es obligatoria.',
            'quantity.numeric'            => 'La cantidad debe ser un valor numérico.',
            'quantity.min'                => 'La cantidad debe ser mayor a 0.',

            // costo
            'unit_cost.numeric'           => 'El costo unitario debe ser un valor numérico.',
            'unit_cost.min'               => 'El costo unitario no puede ser negativo.',

            // referencia
            'reference_type.in'           => 'El tipo de referencia no es válido.',
            'reference_id.uuid'           => 'El ID de referencia no es válido.',
            'reference_id.required_with'  => 'El ID de referencia es obligatorio cuando se indica el tipo.',

            // notas
            'notes.max'                   => 'Las notas no pueden superar los 500 caracteres.',
        ];
    }
}