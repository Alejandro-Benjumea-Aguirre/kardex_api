<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\RegisterData;
use App\Repositories\Interfaces\CompanyRepositoryExtendedInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\EmailService;
use Illuminate\Support\Facades\DB;

class RegisterAction
{
    public function __construct(
        private readonly CompanyRepositoryExtendedInterface $companyRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailService $emailService,
    ) {}

    public function __invoke(RegisterData $data): array
    {
        return DB::transaction(function () use ($data) {

            $company = $this->companyRepository->create([
                'name'       => $data->company_name,
                'nit'        => $data->company_nit,
                'sector'     => $data->company_sector,
                'phone'      => $data->company_phone,
                'address'    => $data->company_address,
                'city_id'    => $data->company_city_id,
                'country_id' => $data->company_country_id,
                'website'    => $data->company_website,
                'slug'       => $data->company_slug,
                'plan'       => $data->company_plan,
                'logo_url'   => $data->company_logo_url,
                'is_active'  => true,
            ]);

            $user = $this->userRepository->create([
                'company_id'        => $company->id,
                'first_name'        => $data->user_first_name,
                'last_name'         => $data->user_last_name,
                'email'             => $data->user_email,
                'password'          => $data->user_password,
                'is_active'         => true,
                'is_email_verified' => true,
            ]);

            $user->assignRole('admin');

            $token = auth()->login($user);

            return compact('company', 'user', 'token');
        });
    }
}
