<?php

declare(strict_types=1);

namespace App\Http\Requests\Category;

class UpdateCategoryRequest extends \App\Http\Requests\ApiFormRequest
{
    public function rules(): array
    {

        $userId = $this->route('category');

        return [
            'name'          => ['sometimes', 'string', 'max:50'],
            'description'   => ['sometimes', 'string', 'max:500'],
            'image_url'     => ['sometimes', 'nullable', 'string', 'max:50'],
            'slug'          => ['required', 'string']
        ];
    }
}
