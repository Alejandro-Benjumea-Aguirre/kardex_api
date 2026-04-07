<?php

declare(strict_types=1);

namespace App\Http\Requests\Roles;

class UpdateRoleRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            'display_name' => ['sometimes', 'string', 'max:150'],
            'description'  => ['sometimes', 'nullable', 'string', 'max:500'],
            'is_default'   => ['sometimes', 'boolean'],
            'is_active'    => ['sometimes', 'boolean'],
        ];
    }
}
