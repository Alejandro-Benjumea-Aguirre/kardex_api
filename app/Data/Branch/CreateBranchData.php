<?php

declare(strict_types=1);

namespace App\Data\Branch;

use Spatie\LaravelData\Data;

class CreateBranchData extends Data
{
    public function __construct(

        // ─── RELACIÓN ────────────────────────────────────
        public readonly string  $company_id,

        // ─── DATOS BÁSICOS ────────────────────────────────
        public readonly string  $name,
        public readonly string  $code,

        // ─── DIRECCIÓN ───────────────────────────────────
        public readonly ?string $address   = null,
        public readonly ?string $city      = null,
        public readonly ?string $state     = null,
        public readonly string  $country   = 'CO',

        public readonly ?float  $latitude  = null,
        public readonly ?float  $longitude = null,

        // ─── CONTACTO ────────────────────────────────────
        public readonly ?string $phone     = null,
        public readonly ?string $email     = null,

        // ─── CONFIGURACIÓN ───────────────────────────────
        public readonly array   $settings  = [
            'opening_time'     => '08:00',
            'closing_time'     => '20:00',
            'receipt_printer'  => null,
            'allow_credit'     => false,
        ],

        // ─── ESTADO ──────────────────────────────────────
        public readonly bool    $is_active = true,
        public readonly bool    $is_main   = false,

    ) {}
}