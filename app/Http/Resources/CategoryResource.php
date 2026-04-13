<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'id'            => $this->id,
			'company_id'    => $this->company_id,
			'parent_id'     => $this->parent_id,
			'name'          => $this->name,
			'slug'          => $this->slug,
			'description'   => $this->description,
			'image_url'     => $this->image_url,
		];
	}
}