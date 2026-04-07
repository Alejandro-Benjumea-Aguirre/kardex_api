<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\ResetPasswordData;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\TokenService;
use App\Exceptions\Auth\InvalidCredentialsException;

/**
 * ResetPasswordAction
 *
 * Resetea la contraseña del usuario usando el token de recuperación
 */
class ResetPasswordAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenService $tokenService,
    ) {}

    /**
     * @throws InvalidCredentialsException si el token es inválido o expiró
     */
    public function __invoke(ResetPasswordData $data): void
    {
        // Buscar el usuario por el token de reset
        $user = $this->userRepository->findByPasswordResetToken($data->token);

        if (! $user) {
            throw new InvalidCredentialsException(
                'El enlace de recuperación es inválido o ha expirado.'
            );
        }

        // Actualizar el password — el cast 'hashed' en el Model
        // lo hashea automáticamente
        $this->userRepository->update($user, ['password' => $data->password]);

        // Limpiar el token de reset (no puede reutilizarse)
        $this->userRepository->clearPasswordResetToken($user);

        // Revocar TODOS los refresh tokens — el usuario debe
        // loguearse de nuevo en todos los dispositivos después
        // de cambiar el password. Esto es una buena práctica
        // de seguridad: si alguien hackeó la cuenta y cambió el
        // password, el dueño legítimo también queda deslogueado
        // y se da cuenta del problema.
        $this->tokenService->revokeAllUserTokens($user->id);
    }
}
