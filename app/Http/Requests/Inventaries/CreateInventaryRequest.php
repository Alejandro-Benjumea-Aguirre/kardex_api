<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

class CreateInventoryRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            // ─── RELACIONES ───────────────────────────────
            'branch_id'          => ['required', 'uuid', 'exists:branches,id'],
            'product_variant_id' => ['required', 'uuid', 'exists:product_variants,id',
                                        // No puede existir ya el mismo variant en la misma sucursal
                                        \Illuminate\Validation\Rule::unique('inventory', 'product_variant_id')
                                            ->where('branch_id', request('branch_id'))],

            // ─── STOCK ────────────────────────────────────
            'quantity'           => ['required', 'numeric', 'min:0'],
            'min_stock'          => ['nullable', 'numeric', 'min:0'],
            'max_stock'          => ['nullable', 'numeric', 'min:0',
                                        'gt:min_stock'],        // max debe ser mayor que min
            'avg_cost'           => ['nullable', 'numeric', 'min:0'],

            // ─── UBICACIÓN ────────────────────────────────
            'location'           => ['nullable', 'string', 'max:50'],

            // ─── MOVIMIENTO INICIAL ───────────────────────
            // Se crea automáticamente un stock_movement tipo 'initial'
            'notes'              => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            // relaciones
            'branch_id.required'             => 'La sucursal es obligatoria.',
            'branch_id.uuid'                 => 'El ID de la sucursal no es válido.',
            'branch_id.exists'               => 'La sucursal no existe.',

            'product_variant_id.required'    => 'La variante del producto es obligatoria.',
            'product_variant_id.uuid'        => 'El ID de la variante no es válido.',
            'product_variant_id.exists'      => 'La variante del producto no existe.',
            'product_variant_id.unique'      => 'Esta variante ya tiene inventario en esta sucursal.',

            // stock
            'quantity.required'              => 'La cantidad inicial es obligatoria.',
            'quantity.numeric'               => 'La cantidad debe ser un valor numérico.',
            'quantity.min'                   => 'La cantidad no puede ser negativa.',

            'min_stock.numeric'              => 'El stock mínimo debe ser un valor numérico.',
            'min_stock.min'                  => 'El stock mínimo no puede ser negativo.',

            'max_stock.numeric'              => 'El stock máximo debe ser un valor numérico.',
            'max_stock.min'                  => 'El stock máximo no puede ser negativo.',
            'max_stock.gt'                   => 'El stock máximo debe ser mayor al stock mínimo.',

            'avg_cost.numeric'               => 'El costo promedio debe ser un valor numérico.',
            'avg_cost.min'                   => 'El costo promedio no puede ser negativo.',

            // ubicación
            'location.max'                   => 'La ubicación no puede superar los 50 caracteres.',

            // notas
            'notes.max'                      => 'Las notas no pueden superar los 500 caracteres.',
        ];
    }
}