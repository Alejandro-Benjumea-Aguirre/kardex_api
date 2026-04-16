<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'name'              => $this->name,
			'native_name'       => $this->native_name,
			'iso2'              => $this->iso2,
			'iso3'              => $this->iso3,
			'phone_code'        => $this->phone_code,
			'capital'           => $this->capital,
			'currency'          => $this->currency,
			'currency_symbol'   => $this->currency_symbol,
			'region'            => $this->region,
			'subregion'         => $this->subregion,
			'flag'              => $this->flag,
		];
	}
}