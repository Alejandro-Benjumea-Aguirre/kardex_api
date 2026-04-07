<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'first_name' => $this->first_name,
            'last_name'  => $this->last_name,
            'full_name'  => $this->full_name,    // accessor del Model
            'initials'   => $this->initials,
            'email'      => $this->email,
            'phone'      => $this->phone,
            'avatar_url' => $this->avatar_url,

            'company'    => $this->whenLoaded('company', fn() => [
                'id'   => $this->company->id,
                'name' => $this->company->name,
                'slug' => $this->company->slug,
            ]),

            'roles' => $this->whenLoaded('roles', fn() =>
                $this->roles->map(fn($role) => [
                    'id'           => $role->id,
                    'name'         => $role->name,
                    'display_name' => $role->display_name,
                    'scope' => [
                        'branch_id' => $role->pivot->branch_id,
                        'expires_at' => $role->pivot->expires_at,
                    ],
                ])
            ),

            'branches' => $this->whenLoaded('branches', fn() =>
                $this->branches->map(fn($branch) => [
                    'id'         => $branch->id,
                    'name'       => $branch->name,
                    'code'       => $branch->code,
                    'is_default' => $branch->pivot->is_default,
                ])
            ),

            'status' => [
                'is_active'         => $this->is_active,
                'is_email_verified' => $this->is_email_verified,
            ],

            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at'    => $this->created_at->toISOString(),
            'updated_at'    => $this->updated_at->toISOString(),
        ];

    }
}
