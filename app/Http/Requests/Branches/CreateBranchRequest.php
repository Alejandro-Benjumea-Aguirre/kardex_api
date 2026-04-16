<?php

declare(strict_types=1);

namespace App\Http\Requests\Branch;

use Illuminate\Validation\Rule;

class CreateBranchRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            // ─── RELACIÓN ────────────────────────────────
            'company_id'              => ['required', 'uuid', 'exists:companies,id'],

            // ─── DATOS BÁSICOS ────────────────────────────
            'name'                    => ['required', 'string', 'max:100'],
            'code'                    => ['required', 'string', 'max:20',
                                            Rule::unique('branches', 'code')
                                                ->where('company_id', request('company_id'))],

            // ─── DIRECCIÓN ───────────────────────────────
            'address'                 => ['nullable', 'string', 'max:255'],
            'city'                    => ['nullable', 'string', 'max:100'],
            'state'                   => ['nullable', 'string', 'max:100'],
            'country'                 => ['nullable', 'string', 'size:2'],
            'latitude'                => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'               => ['nullable', 'numeric', 'between:-180,180'],

            // ─── CONTACTO ────────────────────────────────
            'phone'                   => ['nullable', 'string', 'max:20'],
            'email'                   => ['nullable', 'email', 'max:254'],

            // ─── CONFIGURACIÓN ───────────────────────────
            'settings'                => ['nullable', 'array'],
            'settings.opening_time'   => ['nullable', 'date_format:H:i'],
            'settings.closing_time'   => ['nullable', 'date_format:H:i',
                                            'after:settings.opening_time'],
            'settings.receipt_printer'=> ['nullable', 'string'],
            'settings.allow_credit'   => ['nullable', 'boolean'],

            // ─── ESTADO ──────────────────────────────────
            'is_active'               => ['nullable', 'boolean'],
            'is_main'                 => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            // company
            'company_id.required'              => 'La empresa es obligatoria.',
            'company_id.uuid'                  => 'El ID de la empresa no es válido.',
            'company_id.exists'                => 'La empresa no existe.',

            // datos básicos
            'name.required'                    => 'El nombre de la sucursal es obligatorio.',
            'name.max'                         => 'El nombre no puede superar los 100 caracteres.',
            'code.required'                    => 'El código de la sucursal es obligatorio.',
            'code.max'                         => 'El código no puede superar los 20 caracteres.',
            'code.unique'                      => 'Este código ya está en uso en esta empresa.',

            // dirección
            'address.max'                      => 'La dirección no puede superar los 255 caracteres.',
            'city.max'                         => 'La ciudad no puede superar los 100 caracteres.',
            'state.max'                        => 'El departamento no puede superar los 100 caracteres.',
            'country.size'                     => 'El país debe ser un código de 2 letras (Ej: CO, US).',
            'latitude.numeric'                 => 'La latitud debe ser un valor numérico.',
            'latitude.between'                 => 'La latitud debe estar entre -90 y 90.',
            'longitude.numeric'                => 'La longitud debe ser un valor numérico.',
            'longitude.between'                => 'La longitud debe estar entre -180 y 180.',

            // contacto
            'phone.max'                        => 'El teléfono no puede superar los 20 caracteres.',
            'email.email'                      => 'El correo electrónico no es válido.',
            'email.max'                        => 'El correo no puede superar los 254 caracteres.',

            // settings
            'settings.opening_time.date_format'=> 'La hora de apertura debe tener formato HH:MM.',
            'settings.closing_time.date_format'=> 'La hora de cierre debe tener formato HH:MM.',
            'settings.closing_time.after'      => 'La hora de cierre debe ser mayor a la de apertura.',
        ];
    }
}