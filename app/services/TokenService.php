<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use App\Exceptions\Auth\TokenExpiredException;
use App\Exceptions\Auth\TokenInvalidException;

// ═══════════════════════════════════════════════════════════
// TokenService — JWT Access Token + Refresh Token
//

class TokenService
{
    private const ACCESS_TOKEN_TTL_MINUTES  = 15;

    private const REFRESH_TOKEN_TTL_DAYS    = 30;

    private const ALGORITHM = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly string $appName,
    ) {}

    // ═══════════════════════════════════════════════════════
    // GENERACIÓN DE TOKENS
    // ═══════════════════════════════════════════════════════

    public function generateAccessToken(User $user, string $branchId): string
    {
        $now = time();

        $payload = [
            // CLAIMS ESTÁNDAR (RFC 7519):

            'iss' => $this->appName,

            'sub' => $user->id,

            'iat' => $now,
            'exp' => $now + (self::ACCESS_TOKEN_TTL_MINUTES * 60),

            'jti' => \Str::uuid()->toString(),

            // CLAIMS PRIVADOS (propios del sistema):
            'company_id' => $user->company_id,
            'branch_id'  => $branchId,
            'permissions' => $user->getCachedPermissions($branchId),
        ];

        return JWT::encode($payload, $this->secret, self::ALGORITHM);
    }

    /**
     * Genera el refresh token — un string aleatorio opaco.
     * Se guarda en Redis asociado al usuario.
     */
    public function generateRefreshToken(User $user, string $branchId): string
    {
        $token = bin2hex(random_bytes(64));

        $ttl = now()->addDays(self::REFRESH_TOKEN_TTL_DAYS);

        $data = [
            'user_id'    => $user->id,
            'company_id' => $user->company_id,
            'branch_id'  => $branchId,
            'created_at' => now()->toISOString(),
        ];

        cache()->put(
            $this->refreshTokenKey($token),
            $data,
            $ttl
        );

        $this->addToUserTokensList($user->id, $token, $ttl);

        return $token;
    }

    // ═══════════════════════════════════════════════════════
    // VALIDACIÓN DE TOKENS
    // ═══════════════════════════════════════════════════════

    /**
     * Valida y decodifica un access token JWT.
     * Lanza excepciones específicas según el tipo de error.
     *
     * @throws TokenExpiredException
     * @throws TokenInvalidException
     */
    public function validateAccessToken(string $token): object
    {
        try {
            $payload = JWT::decode($token, new Key($this->secret, self::ALGORITHM));
        } catch (ExpiredException $e) {
            throw new TokenExpiredException('El token de acceso ha expirado.');
        } catch (SignatureInvalidException $e) {
            throw new TokenInvalidException('La firma del token es inválida.');

        } catch (\Exception $e) {
            throw new TokenInvalidException('Token inválido: ' . $e->getMessage());
        }

        if ($this->isBlacklisted($payload->jti)) {
            throw new TokenInvalidException('El token ha sido revocado.');
        }

        $invalidatedAt = cache()->get("user_invalidated_at:{$payload->sub}");
        if ($invalidatedAt && $payload->iat <= $invalidatedAt) {
            throw new TokenInvalidException('El token ha sido revocado.');
        }

        return $payload;
    }

    public function validateRefreshToken(string $token): ?array
    {
        $data = cache()->get($this->refreshTokenKey($token));

        if (! $data) {
            return null;
        }

        return $data;
    }

    // ═══════════════════════════════════════════════════════
    // RENOVACIÓN Y REVOCACIÓN
    // ═══════════════════════════════════════════════════════

    public function rotateRefreshToken(string $oldToken, User $user, string $branchId): string
    {
        cache()->forget($this->refreshTokenKey($oldToken));

        return $this->generateRefreshToken($user, $branchId);
    }

    public function revokeAccessToken(string $jti, int $expiresAt): void
    {
        $ttl = $expiresAt - time();

        if ($ttl > 0) {
            cache()->put(
                "jwt_blacklist:{$jti}",
                true,
                now()->addSeconds($ttl)
            );
        }
    }

    public function revokeAllUserTokens(string $userId): void
    {
        $listKey = "user_tokens:{$userId}";
        $tokens  = cache()->get($listKey, []);

        foreach ($tokens as $tokenData) {
            cache()->forget($this->refreshTokenKey($tokenData['token']));
        }

        cache()->forget($listKey);

        cache()->put(
            "user_invalidated_at:{$userId}",
            time(),
            now()->addMinutes(self::ACCESS_TOKEN_TTL_MINUTES)
        );
    }

    public function revokeRefreshToken(string $token): void
    {
        cache()->forget($this->refreshTokenKey($token));
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ═══════════════════════════════════════════════════════

    private function refreshTokenKey(string $token): string
    {
        return 'refresh_token:' . hash('sha256', $token);
    }

    private function isBlacklisted(string $jti): bool
    {
        return cache()->has("jwt_blacklist:{$jti}");
    }

    private function addToUserTokensList(string $userId, string $token, \Carbon\Carbon $expiresAt): void
    {
        $listKey = "user_tokens:{$userId}";
        $list    = cache()->get($listKey, []);

        $list = array_filter($list, fn($t) => $t['expires_at'] > now()->timestamp);

        $list[] = [
            'token'      => hash('sha256', $token),
            'expires_at' => $expiresAt->timestamp,
        ];

        cache()->put($listKey, array_values($list), $expiresAt);
    }
}
