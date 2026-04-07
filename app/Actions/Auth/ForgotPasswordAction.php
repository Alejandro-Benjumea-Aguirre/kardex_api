<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\EmailService;

/**
 * ForgotPasswordAction
 *
 * CONCEPTO: Flujo de recuperación de contraseña
 *
 * FLUJO:
 *   1. Usuario envía su email
 *   2. Si existe: generamos token y enviamos email con link
 *   3. Si no existe: respondemos igual (no revelar emails)
 *   4. El link tiene el token: /reset-password?token=xxxxx
 *   5. El token expira en 1 hora
 */
class ForgotPasswordAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly EmailService $emailService,
    ) {}

    public function __invoke(string $email): void
    {
        $user = $this->userRepository->findByEmail($email);

        // Si el usuario no existe, no hacemos nada pero tampoco
        // lanzamos error — nunca revelamos si un email está registrado.
        // La respuesta al cliente es siempre: "si el email existe,
        // recibirás las instrucciones"
        if (! $user || ! $user->isActive()) {
            return;
        }

        // Generar token seguro
        $token     = bin2hex(random_bytes(32)); // 64 chars hex
        $expiresAt = now()->addHour();

        // Guardar token en DB (tabla password_reset_tokens)
        $this->userRepository->setPasswordResetToken($user, $token, $expiresAt);

        // Enviar email con el link de reset
        $this->emailService->sendPasswordResetEmail($user, $token);
    }
}
