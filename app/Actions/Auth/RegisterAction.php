<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\RegisterUserData;
use App\Models\Company;
use App\Models\User;
use App\Repositories\Interfaces\CompanyRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\EmailService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * RegisterAction
 *
 * Registra una empresa y nuevo usuario en el sistema y envía email de verificación
 */
class RegisterAction
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailService $emailService,
    ) {}

    public function __invoke(RegisterUserData $data): array
    {
        return DB::transaction(function () use ($data) {

            $company = $this->companyRepository->create([
                'name'     => $data->company_name,
                'slug'     => $data->company_slug,
                'plan'     => $data->company_plan,
                'logo_url' => $data->company_logo_url,
            ]);

            $user = $this->userRepository->create([
                'company_id'        => $company->id,
                'first_name'        => $data->first_name,
                'last_name'         => $data->last_name,
                'email'             => $data->email,
                'password'          => $data->password,
                'is_active'         => true,
                'is_email_verified' => true, // TODO: cambiar a false y activar email verification en producción
            ]);

            $user->assignRole('admin');

            $token = auth()->login($user);

            return compact('company', 'user', 'token');

        }
    }
}
