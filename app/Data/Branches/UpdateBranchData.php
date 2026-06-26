<?php

declare(strict_types=1);

namespace App\Data\Branches;

use Spatie\LaravelData\Data;

class UpdateBranchData extends Data
{
    public function __construct(

        // ─── DATOS BÁSICOS ────────────────────────────────
        public readonly ?string $name      = null,
        public readonly ?string $code      = null,

        // ─── DIRECCIÓN ───────────────────────────────────
        public readonly ?string $address   = null,
        public readonly ?string $city      = null,
        public readonly ?string $state     = null,
        public readonly ?string $country   = null,

        public readonly ?float  $latitude  = null,
        public readonly ?float  $longitude = null,

        // ─── CONTACTO ────────────────────────────────────
        public readonly ?string $phone     = null,
        public readonly ?string $email     = null,

        // ─── CONFIGURACIÓN ───────────────────────────────
        public readonly ?array  $settings  = null,

        // ─── ESTADO ──────────────────────────────────────
        public readonly ?bool   $is_active = null,
        public readonly ?bool   $is_main   = null,

    ) {}
}