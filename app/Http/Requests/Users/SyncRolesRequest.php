<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

class SyncRolesRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {
        return [
            // array: el campo debe ser un array
            // array.*: cada elemento del array debe ser UUID válido que existe en roles
            'role_ids'   => ['required', 'array'],
            'role_ids.*' => ['uuid', 'exists:roles,id'],
            'branch_id'  => ['nullable', 'uuid', 'exists:branches,id'],
        ];
    }
}
