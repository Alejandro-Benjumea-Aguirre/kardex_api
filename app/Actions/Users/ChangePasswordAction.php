<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Data\Users\ChangePasswordData;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryExtendedInterface;
use App\Exceptions\Auth\InvalidCredentialsException;


// ═══════════════════════════════════════════════════════════
// ChangePasswordAction
// ═══════════════════════════════════════════════════════════

class ChangePasswordAction
{
    public function __construct(
        private readonly UserRepositoryExtendedInterface $userRepository,
    ) {}

    /**
     * @throws InvalidCredentialsException si el password actual es incorrecto
     */
    public function __invoke(User $user, ChangePasswordData $data): void
    {
        // Verificar que el password actual sea correcto
        if (! \Hash::check($data->current_password, $user->password)) {
            throw new InvalidCredentialsException(
                'La contraseña actual es incorrecta.'
            );
        }

        $this->userRepository->update($user, ['password' => $data->password]);

        // Revocar todos los tokens excepto el actual
        // El usuario sigue logueado en este dispositivo pero
        // los otros dispositivos deben volver a loguear
        app(\App\Services\TokenService::class)->revokeAllUserTokens($user->id);
    }
}
