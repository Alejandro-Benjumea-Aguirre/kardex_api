<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'country_id'        => $this->country_id,
			'name'              => $this->name,
			'dane_code'         => $this->dane_code,
			'department'        => $this->department,
			'department_code'   => $this->department_code,
			'latitude'          => $this->latitude,
			'longitude'         => $this->longitude,
		];
	}
}