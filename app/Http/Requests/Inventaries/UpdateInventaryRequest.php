<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

class UpdateInventoryRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            // ─── STOCK ────────────────────────────────────
            // quantity NO se actualiza directamente
            // siempre se hace a través de stock_movements
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'max_stock' => ['nullable', 'numeric', 'min:0',
                                'gt:min_stock'],
            'avg_cost'  => ['nullable', 'numeric', 'min:0'],

            // ─── UBICACIÓN ────────────────────────────────
            'location'  => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            // stock
            'min_stock.numeric' => 'El stock mínimo debe ser un valor numérico.',
            'min_stock.min'     => 'El stock mínimo no puede ser negativo.',

            'max_stock.numeric' => 'El stock máximo debe ser un valor numérico.',
            'max_stock.min'     => 'El stock máximo no puede ser negativo.',
            'max_stock.gt'      => 'El stock máximo debe ser mayor al stock mínimo.',

            'avg_cost.numeric'  => 'El costo promedio debe ser un valor numérico.',
            'avg_cost.min'      => 'El costo promedio no puede ser negativo.',

            // ubicación
            'location.max'      => 'La ubicación no puede superar los 50 caracteres.',
        ];
    }
}