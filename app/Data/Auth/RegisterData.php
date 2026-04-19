<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

class RegisterData extends Data
{
    public function __construct(
        // ─── Empresa ─────────────────────────────────────────
        public readonly string  $company_name,
        public readonly string  $company_nit,
        public readonly string  $company_sector,
        public readonly string  $company_phone,
        public readonly string  $company_address,
        public readonly ?string $company_city_id    = null,
        public readonly ?string $company_country_id = null,
        public readonly ?string $company_website    = null,
        public readonly ?string $company_slug       = null,
        public readonly string  $company_plan       = 'free',
        public readonly ?string $company_logo_url   = null,

        // ─── Usuario admin ────────────────────────────────────
        public readonly string  $user_first_name            = '',
        public readonly string  $user_last_name             = '',
        public readonly string  $user_email                 = '',
        public readonly string  $user_password              = '',
        public readonly string  $user_password_confirmation = '',
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            company_name:       $data['company']['name'],
            company_nit:        $data['company']['nit'],
            company_sector:     $data['company']['sector'],
            company_phone:      $data['company']['phone'],
            company_address:    $data['company']['address'],
            company_city_id:    $data['company']['city_id']    ?? null,
            company_country_id: $data['company']['country_id'] ?? null,
            company_website:    $data['company']['website']    ?? null,
            company_slug:       $data['company']['slug']       ?? null,
            company_plan:       $data['company']['plan']       ?? 'free',
            company_logo_url:   $data['company']['logo_url']   ?? null,

            user_first_name:            $data['user']['first_name'],
            user_last_name:             $data['user']['last_name'],
            user_email:                 $data['user']['email'],
            user_password:              $data['user']['password'],
            user_password_confirmation: $data['user']['password_confirmation'] ?? '',
        );
    }
}
