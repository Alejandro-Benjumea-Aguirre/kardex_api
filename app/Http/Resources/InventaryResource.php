<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventaryResource extends JsonResource
{
	public function toArray(Request $request): array
	{
		return [
			'branch_id'          => $this->branch_id,
			'product_variant_id' => $this->product_variant_id,
			'quantity'           => $this->quantity,
			'min_stock'          => $this->min_stock,
			'max_stock'          => $this->max_stock,
			'location'           => $this->location,
			'avg_cost'           => $this->avg_cost
		];
	}
}