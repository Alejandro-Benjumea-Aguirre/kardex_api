<?php

declare(strict_types=1);

namespace App\Http\Requests\Roles;

class CreateRoleRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            'display_name'     => ['required', 'string', 'max:150'],
            'description'      => ['nullable', 'string', 'max:500'],
            'is_default'       => ['boolean'],
            // permission_ids es opcional al crear — podés crear el rol vacío
            // y asignar permisos después
            'permission_ids'   => ['nullable', 'array'],
            'permission_ids.*' => ['uuid', 'exists:permissions,id'],
        ];
    }
}
