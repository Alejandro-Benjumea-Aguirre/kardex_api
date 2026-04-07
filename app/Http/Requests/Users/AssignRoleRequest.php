<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

class AssignRoleRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            'role_id'   => ['required', 'uuid', 'exists:roles,id'],
            'branch_id' => ['nullable', 'uuid', 'exists:branches,id'],
        ];
    }
}
