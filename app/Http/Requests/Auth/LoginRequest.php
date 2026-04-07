<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Http\Requests\ApiFormRequest;

class LoginRequest extends ApiFormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->email) {
            $this->merge(['email' => strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        return [
            'email'     => ['required', 'string', 'email', 'max:254'],
            'password'  => ['required', 'string', 'min:6'],
            'branch_id' => ['nullable', 'string'],
        ];
    }
}
