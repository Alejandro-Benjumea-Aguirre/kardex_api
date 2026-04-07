<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\RegisterUserData;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\EmailService;

/**
 * RegisterAction
 *
 * Registra un nuevo usuario en el sistema y envía email de verificación
 */
class RegisterAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailService $emailService,
    ) {}

    public function __invoke(RegisterUserData $data): User
    {
        // Crear el usuario — el cast 'hashed' en el Model
        // hashea el password automáticamente.
        $user = $this->userRepository->create([
            'company_id'        => $data->company_id,
            'first_name'        => $data->first_name,
            'last_name'         => $data->last_name,
            'email'             => $data->email,
            'password'          => $data->password,
            'is_active'         => true,
            'is_email_verified' => true, // TODO: cambiar a false y activar email verification en producción
        ]);

        return $user;
    }
}
