<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Exceptions\Auth\InvalidCredentialsException;

/**
 * VerifyEmailAction
 *
 * Verifica el email del usuario usando el token enviado por correo
 */
class VerifyEmailAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    /**
     * @throws InvalidCredentialsException si el token es inválido o expiró
     */
    public function __invoke(string $userId, string $token): void
    {
        $user = $this->userRepository->findById($userId);

        if (! $user) {
            throw new InvalidCredentialsException('Link de verificación inválido.');
        }

        if ($user->isEmailVerified()) {
            // Ya verificado — no es error, simplemente no hacemos nada
            return;
        }

        // Verificar el token contra Redis
        $storedHash = cache()->get("email_verify:{$userId}");

        if (! $storedHash || ! hash_equals($storedHash, hash('sha256', $token))) {
            // hash_equals: comparación en tiempo constante
            // Previene timing attacks donde el atacante mide cuánto
            // tarda la comparación para adivinar el token byte a byte
            throw new InvalidCredentialsException('El enlace de verificación es inválido o ha expirado.');
        }

        $this->userRepository->markEmailAsVerified($user);
    }
}
