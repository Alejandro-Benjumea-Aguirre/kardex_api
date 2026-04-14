<?php

declare(strict_types=1);

namespace App\Http\Requests\Barcodes;

use Illuminate\Validation\Rule;

class CreateBarcodeRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            'code'       => ['required', 'string', 'max:100', 'unique:barcodes,code'],
            'type'       => ['required', Rule::in(['ean13', 'ean8', 'upc', 'qr', 'custom'])],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'El código de barras es obligatorio.',
            'code.unique'   => 'Este código ya está registrado.',
            'code.max'      => 'El código no puede superar los 100 caracteres.',
            'type.required' => 'El tipo de código es obligatorio.',
            'type.in'       => 'El tipo debe ser: ean13, ean8, upc, qr o custom.',
        ];
    }
}