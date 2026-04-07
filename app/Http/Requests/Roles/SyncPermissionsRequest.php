<?php

declare(strict_types=1);

namespace App\Http\Requests\Roles;

class SyncPermissionsRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            'permission_ids'   => ['present', 'array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'permission_ids.required'  => 'Debés enviar el array de permisos (puede estar vacío).',
            'permission_ids.*.exists'  => 'Uno o más permisos enviados no existen.',
        ];
    }
}
