<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// ═══════════════════════════════════════════════════════════
// API Resources
//
// CONCEPTO: ¿Por qué no devolver el Model directamente?
// ═══════════════════════════════════════════════════════════
//
// SIN Resource (problemático):
//   return response()->json($user);
//   → Devuelve TODOS los campos del modelo, incluyendo
//     campos que no deberían salir (password, deleted_at, etc.)
//   → El formato puede cambiar si cambia el modelo
//   → No hay un contrato explícito de qué devuelve la API
//
// CON Resource (correcto):
//   return new UserResource($user);
//   → Solo devuelve los campos que definís aquí
//   → El Resource es el contrato de la API
//   → Si cambia el modelo, el Resource protege la API de cambiar
//   → Podés transformar los datos (formatear fechas, calcular campos)
//
// Es la diferencia entre exponer la DB directamente
// o exponer una capa controlada de tu API.
// ═══════════════════════════════════════════════════════════

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

            // whenLoaded: solo incluye la relación si fue cargada con ->load() o with()
            // Si no fue cargada, el campo no aparece en el JSON
            // Esto evita N+1 queries accidentales
            'company'    => $this->whenLoaded('company', fn() => [
                'id'   => $this->company->id,
                'name' => $this->company->name,
                'slug' => $this->company->slug,
            ]),

            // ─── CONCEPTO: whenLoaded() vs siempre cargar ────────
            //
            // whenLoaded('roles') solo incluye roles en el JSON
            // si fueron cargados con ->load('roles') o with('roles').
            //
            // Sin esto, si olvidás el eager loading, Eloquent haría
            // una query extra por cada usuario (problema N+1).
            // Con whenLoaded(), si no fueron cargados, el campo
            // simplemente no aparece en el JSON — no hace query.
            //
            // En el listado de usuarios NO cargamos roles (performance).
            // En el detalle de un usuario SÍ los cargamos.
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

            // Las fechas se formatean en ISO 8601 para que el frontend
            // las parsee correctamente sin ambigüedad de zona horaria
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at'    => $this->created_at->toISOString(),
            'updated_at'    => $this->updated_at->toISOString(),
        ];

        // NOTA: password, deleted_at, remember_token
        // no aparecen aquí → nunca salen en la API
    }
}
