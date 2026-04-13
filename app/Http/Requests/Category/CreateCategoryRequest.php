<?php

declare(strict_types=1);

namespace App\Http\Requests\Category;

class CreateCategoryRequest extends \App\Http\Requests\ApiFormRequest
{
	public function rules(): array
	{
		return [
			'company_id'    => ['nullable', 'uuid', 'exists:company,id'],
			'parent_id'     => ['nullable', 'uuid', 'exists:category,id'],
			'name'          => ['required', 'string', 'max:50'],
			'slug'          => ['required'],
			'description'   => ['required', 'string', 'max:500'],
			'image_url'     => ['nullable', 'string', 'max:50'],
		];
	}

	public function messages(): array
	{
		return [
			'name.max'     			=> 'El maximo de caracteres es de 50.',
			'description.max'  	=> 'El maximo de caracteres es de 500.',
			'image_url.max' 		=> 'El maximo de caracteres es de 50.',
			'slug.required'   	=> 'El campo es obligatorio.',
			'company_id.exists' => 'La compañia no existe.',
			'parent_id.exists'  => 'La categoria padre no existe.',
		];
	}
}
