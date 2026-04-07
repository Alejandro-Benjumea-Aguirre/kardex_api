<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

class UpdateUserRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {

        $userId = $this->route('user');

        return [
            'first_name' => ['sometimes', 'string', 'max:50'],
            'last_name'  => ['sometimes', 'string', 'max:50'],
            'phone'      => ['sometimes', 'nullable', 'string', 'max:20'],
            'avatar_url' => ['sometimes', 'nullable', 'url'],
            'email'      => [
                'sometimes', 'string', 'email', 'max:254',
                \Illuminate\Validation\Rule::unique('users', 'email')->ignore($userId),
            ],
        ];
    }
}
