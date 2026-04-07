<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\TokenService;

/**
 * LogoutAllAction
 *
 * Cierra todas las sesiones del usuario en todos los dispositivos
 */
class LogoutAllAction
{
    public function __construct(
        private readonly TokenService $tokenService,
    ) {}

    public function __invoke(User $user, string $jti, int $exp): void
    {
        // Blacklist del access token actual
        $this->tokenService->revokeAccessToken($jti, $exp);

        // Revocar TODOS los refresh tokens del usuario
        // (todos los dispositivos donde está logueado)
        $this->tokenService->revokeAllUserTokens($user->id);

        // Invalidar caché de permisos
        $user->invalidatePermissionsCache();
    }
}
