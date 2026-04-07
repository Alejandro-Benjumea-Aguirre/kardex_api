<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\{JsonResource, ResourceCollection};

class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'display_name' => $this->display_name,
            'description'  => $this->description,

            'flags' => [
                'is_system'  => $this->is_system,
                'is_default' => $this->is_default,
                'is_active'  => $this->is_active,
                'is_global'  => $this->isGlobal(),
            ],

            'company' => $this->whenLoaded('company', fn() => [
                'id'   => $this->company->id,
                'name' => $this->company->name,
            ]),

            // Para el detalle del rol: permisos agrupados por módulo
            'permissions' => $this->whenLoaded('permissions', fn() =>
                $this->permissions
                    ->groupBy('module')
                    ->map(fn($perms, $module) => [
                        'module'      => $module,
                        'permissions' => $perms->map(fn($p) => [
                            'id'           => $p->id,
                            'name'         => $p->name,
                            'display_name' => $p->display_name,
                        ]),
                    ])
                    ->values()
            ),

            // Resumen de cuántos permisos tiene (sin cargar la relación)
            'permissions_count' => $this->whenCounted('permissions'),

            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
