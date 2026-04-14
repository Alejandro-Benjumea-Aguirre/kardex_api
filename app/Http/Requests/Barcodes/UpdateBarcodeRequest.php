<?php

declare(strict_types=1);

namespace App\Http\Requests\Barcodes;

use Illuminate\Validation\Rule;

class UpdateBarcodeRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        $barcodeId = $this->route('barcode');

        return [
            'code'       => ['nullable', 'string', 'max:100',
                                Rule::unique('barcodes', 'code')->ignore($barcodeId)],
            'type'       => ['nullable', Rule::in(['ean13', 'ean8', 'upc', 'qr', 'custom'])],
            'is_primary' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Este código ya está registrado en otro producto.',
            'type.in'     => 'El tipo debe ser: ean13, ean8, upc, qr o custom.',
        ];
    }
}