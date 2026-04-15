<?php

declare(strict_types=1);

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

// ─── RegisterData ───────────────────────────────────────────

class RegisterData extends Data
{

    public function __construct(
        // Empresa
        public readonly string  $company_name,
        public readonly ?string $company_slug     = null,
        public readonly string  $company_plan     = 'free',
        public readonly ?string $company_logo_url = null,

        // Usuario
        public readonly string  $user_first_name   = '',
        public readonly string  $user_last_name    = '',
        public readonly string  $user_email        = '',
        public readonly string  $user_password     = '',
    ) {}

    // Mapea el array anidado del request al DTO plano
    public static function fromRequest(array $data): self
    {
        return new self(
            company_name:     $data['company']['name'],
            company_slug:     $data['company']['slug']     ?? null,
            company_plan:     $data['company']['plan']     ?? 'free',
            company_logo_url: $data['company']['logo_url'] ?? null,
            user_first_name:  $data['user']['first_name'],
            user_last_name:   $data['user']['last_name'],
            user_email:       $data['user']['email'],
            user_password:    $data['user']['password'],
        );
    }
}

