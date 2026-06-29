<?php

declare(strict_types=1);

namespace App\Http\Requests\Categories;

class CreateCategoryRequest extends \App\Http\Requests\ApiFormRequest
{
	public function rules(): array
	{
		return [
			'parent_id'     => ['nullable', 'uuid', 'exists:categories,id'],
			'name'          => ['required', 'string', 'max:50'],
			'slug'          => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
			'description'   => ['required', 'string', 'max:500'],
			'image_url'     => ['nullable', 'string', 'max:500'],
		];
	}

	public function messages(): array
	{
		return [
			'name.required'     => 'El nombre es obligatorio.',
			'name.max'          => 'El nombre no puede superar los 50 caracteres.',
			'description.max'   => 'La descripción no puede superar los 500 caracteres.',
			'image_url.max'     => 'La URL de la imagen no puede superar los 500 caracteres.',
			'slug.required'     => 'El slug es obligatorio.',
			'slug.max'          => 'El slug no puede superar los 100 caracteres.',
			'slug.regex'        => 'El slug solo puede contener letras minúsculas, números y guiones (ej: mi-categoria).',
			'description.required' => 'La descripción es obligatoria.',
			'parent_id.exists'  => 'La categoria padre no existe.',
		];
	}
}
