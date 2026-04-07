<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Services\TokenService;

/**
 * LogoutAction
 *
 * Cierra sesión del dispositivo actual revocando los tokens
 */
class LogoutAction
{
    public function __construct(
        private readonly TokenService $tokenService,
    ) {}

    /**
     * Logout del dispositivo actual.
     *
     * @param string $jti      El JTI del access token actual (para blacklist)
     * @param int    $exp      El timestamp de expiración del access token
     * @param string $refreshToken  El refresh token de la cookie
     */
    public function __invoke(string $jti, int $exp, string $refreshToken): void
    {
        // Agregar el access token a la blacklist hasta que expire
        // (máx 15 minutos). Después de exp, JWT::decode lo rechaza solo.
        $this->tokenService->revokeAccessToken($jti, $exp);

        // Revocar el refresh token de Redis
        $this->tokenService->revokeRefreshToken($refreshToken);
    }
}
