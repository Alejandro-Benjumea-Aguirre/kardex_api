<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\RefreshResultData;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\TokenService;
use App\Exceptions\Auth\TokenInvalidException;

/**
 * RefreshTokenAction
 *
 * CONCEPTO: ¿Qué pasa cuando el access token expira?
 *
 * El cliente recibe 401 con body: { "error": "token_expired" }
 * El cliente toma el refresh token de la cookie HttpOnly
 * y lo manda a POST /api/v1/auth/refresh
 * Esta Action valida el refresh token y genera un nuevo access token.
 *
 * Si el refresh token también expiró o fue revocado,
 * devuelve 401 y el cliente debe mostrar la pantalla de login.
 */
class RefreshTokenAction
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenService $tokenService,
    ) {}

    /**
     * @throws TokenInvalidException si el refresh token no existe o expiró
     */
    public function __invoke(string $refreshToken): RefreshResultData
    {
        // Validar que el refresh token existe en Redis
        $tokenData = $this->tokenService->validateRefreshToken($refreshToken);

        if (! $tokenData) {
            throw new TokenInvalidException('Refresh token inválido o expirado. Iniciá sesión de nuevo.');
        }

        // Cargar el usuario fresco desde la DB
        // No confiamos en los datos del token — siempre verificamos
        // que el usuario siga activo y existiendo
        $user = $this->userRepository->findById($tokenData['user_id']);

        if (! $user || ! $user->isActive()) {
            // Usuario fue desactivado después de que se emitió el token
            $this->tokenService->revokeRefreshToken($refreshToken);
            throw new TokenInvalidException('La cuenta está desactivada.');
        }

        $branchId = $tokenData['branch_id'];

        // Recargar permisos frescos desde la DB
        // Los permisos pueden haber cambiado desde el último login
        $user->loadAndCachePermissions($branchId);

        // ROTACIÓN del refresh token:
        // El viejo se invalida, se genera uno nuevo.
        // Así detectamos si el refresh token fue robado y usado
        // por dos partes diferentes simultáneamente.
        $newRefreshToken = $this->tokenService->rotateRefreshToken(
            $refreshToken,
            $user,
            $branchId
        );

        $newAccessToken = $this->tokenService->generateAccessToken($user, $branchId);

        return new RefreshResultData(
            access_token:  $newAccessToken,
            refresh_token: $newRefreshToken,
            token_type:    'Bearer',
            expires_in:    15 * 60,
        );
    }
}
