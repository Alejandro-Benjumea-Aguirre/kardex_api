<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            // ─── IDENTIFICACIÓN ──────────────────────────
            'id'              => $this->id,
            'company_id'      => $this->company_id,
            'name'            => $this->name,
            'code'            => $this->code,

            // ─── DIRECCIÓN ───────────────────────────────
            'address'         => $this->address,
            'city'            => $this->city,
            'state'           => $this->state,
            'country'         => $this->country,
            'coordinates'     => $this->when(
                $this->latitude && $this->longitude,
                fn() => [
                    'latitude'  => $this->latitude,
                    'longitude' => $this->longitude,
                ]
            ),

            // ─── CONTACTO ────────────────────────────────
            'phone'           => $this->phone,
            'email'           => $this->email,

            // ─── CONFIGURACIÓN ───────────────────────────
            'settings'        => [
                'opening_time'     => $this->settings['opening_time']    ?? '08:00',
                'closing_time'     => $this->settings['closing_time']    ?? '20:00',
                'receipt_printer'  => $this->settings['receipt_printer'] ?? null,
                'allow_credit'     => $this->settings['allow_credit']    ?? false,
            ],

            // ─── NUMERACIÓN ──────────────────────────────
            'invoice_counter' => $this->invoice_counter,

            // ─── ESTADO ──────────────────────────────────
            'is_active'       => $this->is_active,
            'is_main'         => $this->is_main,

            // ─── RELACIONES ──────────────────────────────
            'company'         => $this->whenLoaded('company', fn() => [
                'id'   => $this->company->id,
                'name' => $this->company->name,
                'plan' => $this->company->plan,
            ]),
            'users'           => $this->whenLoaded('users', fn() =>
                $this->users->map(fn($user) => [
                    'id'         => $user->id,
                    'name'       => $user->name,
                    'email'      => $user->email,
                    'is_default' => $user->pivot->is_default,
                    'assigned_at'=> $user->pivot->assigned_at,
                ])
            ),

            // ─── TIMESTAMPS ──────────────────────────────
            'created_at'      => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at'      => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}