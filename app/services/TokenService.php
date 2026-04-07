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
// CONCEPTO: ¿Cómo funciona el sistema de doble token?
// ═══════════════════════════════════════════════════════════
//
// En vez de un solo token de larga duración (peligroso),
// usamos DOS tokens con propósitos distintos:
//
//  ACCESS TOKEN (JWT corto):
//    - Duración: 15 minutos
//    - Contiene: user_id, company_id, branch_id, permissions
//    - Se envía en cada request: Authorization: Bearer {token}
//    - Stateless: la API lo valida sin consultar DB ni Redis
//    - Si es robado, expira en 15 minutos solo
//
//  REFRESH TOKEN (opaco, almacenado en Redis):
//    - Duración: 30 días
//    - NO contiene datos del usuario (es un string aleatorio)
//    - Solo se usa para obtener un nuevo access token
//    - Se guarda en Redis: puede revocarse instantáneamente
//    - Se envía como HttpOnly cookie (no accesible desde JS)
//
// FLUJO COMPLETO:
//
//  1. Login exitoso:
//     → Genera access token (JWT, 15 min)
//     → Genera refresh token (opaco, 30 días, guardado en Redis)
//     → Respuesta: { access_token } + cookie HttpOnly con refresh_token
//
//  2. Request a endpoint protegido:
//     → Cliente envía: Authorization: Bearer {access_token}
//     → Middleware valida el JWT (sin Redis, stateless)
//     → Si válido: request procesado
//     → Si expirado (401): cliente necesita renovar
//
//  3. Renovación del access token:
//     → Cliente envía el refresh_token (desde cookie)
//     → Servidor busca en Redis: ¿existe? ¿no fue revocado?
//     → Si válido: genera nuevo access token
//     → Si inválido/expirado: 401, el usuario debe loguearse de nuevo
//
//  4. Logout:
//     → Servidor borra el refresh token de Redis
//     → El access token sigue "válido" técnicamente hasta que expire
//       (15 min máx) — esto es aceptable y es el tradeoff de JWT stateless
//     → Para revocación inmediata: blacklist en Redis (ver abajo)
// ═══════════════════════════════════════════════════════════

class TokenService
{
    // Cuánto dura el access token
    private const ACCESS_TOKEN_TTL_MINUTES  = 15;

    // Cuánto dura el refresh token
    private const REFRESH_TOKEN_TTL_DAYS    = 30;

    // Algoritmo de firma del JWT
    // HS256: simétrico (misma clave para firmar y verificar)
    // RS256: asimétrico (clave privada firma, pública verifica) — más seguro
    // Para SalesPoint HS256 es suficiente. Para OAuth público → RS256.
    private const ALGORITHM = 'HS256';

    public function __construct(
        // La clave secreta viene de config('jwt.secret') → .env JWT_SECRET
        // Mínimo 32 caracteres aleatorios. Generala con:
        //   php artisan key:generate --show | base64
        private readonly string $secret,
        private readonly string $appName,
    ) {}

    // ═══════════════════════════════════════════════════════
    // GENERACIÓN DE TOKENS
    // ═══════════════════════════════════════════════════════

    /**
     * Genera el access token JWT con los datos del usuario.
     *
     * ANATOMÍA DE UN JWT:
     * Un JWT tiene 3 partes separadas por puntos:
     *   header.payload.signature
     *
     * Header: algoritmo y tipo
     *   { "alg": "HS256", "typ": "JWT" }
     *
     * Payload (claims): datos del usuario
     *   { "sub": "uuid", "iat": 1234, "exp": 1234, ... }
     *
     * Signature: HMAC-SHA256(base64(header) + "." + base64(payload), secret)
     *   Garantiza que nadie modificó el payload sin tener la clave secreta.
     *
     * IMPORTANTE: El payload de un JWT NO es secreto.
     * Cualquiera puede decodificarlo en base64. Solo la FIRMA es segura.
     * Nunca pongas datos sensibles (passwords, tarjetas) en el JWT.
     */
    public function generateAccessToken(User $user, string $branchId): string
    {
        $now = time();

        $payload = [
            // CLAIMS ESTÁNDAR (RFC 7519):

            // iss (issuer): quién generó el token
            'iss' => $this->appName,

            // sub (subject): para quién es el token — el ID del usuario
            'sub' => $user->id,

            // iat (issued at): cuándo fue generado (Unix timestamp)
            'iat' => $now,

            // exp (expiration): cuándo expira
            // Si exp < now() → token inválido → 401 Unauthorized
            'exp' => $now + (self::ACCESS_TOKEN_TTL_MINUTES * 60),

            // jti (JWT ID): ID único del token para poder hacer blacklist
            // sin jti no podrías revocar un token específico
            'jti' => \Str::uuid()->toString(),

            // CLAIMS PRIVADOS (propios del sistema):

            // Datos del contexto del usuario en este token
            'company_id' => $user->company_id,
            'branch_id'  => $branchId,

            // Permisos cacheados — se incluyen directamente en el JWT
            // para que el middleware los lea sin consultar Redis.
            // TRADEOFF: el JWT es más grande, pero no hay I/O en cada request.
            // Si los permisos cambian antes de que expire el token, el usuario
            // sigue teniendo los permisos viejos por hasta 15 minutos.
            // Para permisos críticos → verificar en DB puntualmente.
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
        // 64 bytes aleatorios → 128 caracteres hex
        // Imposible de adivinar por fuerza bruta
        $token = bin2hex(random_bytes(64));

        $ttl = now()->addDays(self::REFRESH_TOKEN_TTL_DAYS);

        // Guardamos en Redis con toda la información necesaria para
        // renovar el access token sin consultar la DB
        $data = [
            'user_id'    => $user->id,
            'company_id' => $user->company_id,
            'branch_id'  => $branchId,
            'created_at' => now()->toISOString(),
        ];

        // Clave Redis: "refresh_token:{hash_del_token}"
        // Hasheamos el token antes de guardarlo, igual que los passwords.
        // Si alguien accede al Redis, no puede usar los tokens que ve.
        cache()->put(
            $this->refreshTokenKey($token),
            $data,
            $ttl
        );

        // También guardamos la lista de refresh tokens activos del usuario
        // para poder revocarlos todos en un logout "de todos los dispositivos"
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
     * @throws TokenExpiredException  si el token expiró
     * @throws TokenInvalidException  si la firma es inválida o el token está en blacklist
     */
    public function validateAccessToken(string $token): object
    {
        try {
            // JWT::decode verifica:
            // 1. Que la firma sea válida (nadie alteró el payload)
            // 2. Que no haya expirado (exp > now)
            // 3. Que el algoritmo sea el esperado (evita ataques de confusión)
            $payload = JWT::decode($token, new Key($this->secret, self::ALGORITHM));

        } catch (ExpiredException $e) {
            // Excepción específica para token expirado.
            // El cliente puede intentar renovar con el refresh token.
            throw new TokenExpiredException('El token de acceso ha expirado.');

        } catch (SignatureInvalidException $e) {
            // La firma no coincide — token manipulado o clave incorrecta.
            throw new TokenInvalidException('La firma del token es inválida.');

        } catch (\Exception $e) {
            throw new TokenInvalidException('Token inválido: ' . $e->getMessage());
        }

        // Verificar blacklist individual (logout de un dispositivo)
        if ($this->isBlacklisted($payload->jti)) {
            throw new TokenInvalidException('El token ha sido revocado.');
        }

        // Verificar invalidación global del usuario (logout-all / cambio de password)
        // Si el token fue emitido ANTES del timestamp de invalidación, es inválido.
        $invalidatedAt = cache()->get("user_invalidated_at:{$payload->sub}");
        if ($invalidatedAt && $payload->iat <= $invalidatedAt) {
            throw new TokenInvalidException('El token ha sido revocado.');
        }

        return $payload;
    }

    /**
     * Valida un refresh token consultando Redis.
     * Devuelve los datos asociados al token o null si es inválido.
     */
    public function validateRefreshToken(string $token): ?array
    {
        $data = cache()->get($this->refreshTokenKey($token));

        if (! $data) {
            return null; // Token no existe o expiró
        }

        return $data;
    }

    // ═══════════════════════════════════════════════════════
    // RENOVACIÓN Y REVOCACIÓN
    // ═══════════════════════════════════════════════════════

    /**
     * Rota el refresh token — invalida el viejo y genera uno nuevo.
     *
     * CONCEPTO: Refresh Token Rotation
     * Cada vez que usás el refresh token para obtener un nuevo access token,
     * el refresh token también se renueva (rotación).
     *
     * BENEFICIO: Si alguien roba el refresh token y lo usa,
     * el token legítimo del usuario queda invalidado, y el sistema
     * detecta que hubo dos usos del mismo token → alerta de seguridad.
     */
    public function rotateRefreshToken(string $oldToken, User $user, string $branchId): string
    {
        // Invalidar el viejo refresh token
        cache()->forget($this->refreshTokenKey($oldToken));

        // Generar uno nuevo
        return $this->generateRefreshToken($user, $branchId);
    }

    /**
     * Revoca un access token específico agregándolo a la blacklist.
     * Útil en logout para invalidar el access token antes de que expire.
     */
    public function revokeAccessToken(string $jti, int $expiresAt): void
    {
        // Solo necesitamos mantener el token en blacklist hasta que expire.
        // Después de exp, JWT::decode lo rechaza automáticamente.
        $ttl = $expiresAt - time();

        if ($ttl > 0) {
            cache()->put(
                "jwt_blacklist:{$jti}",
                true,
                now()->addSeconds($ttl)
            );
        }
    }

    /**
     * Revoca todos los refresh tokens de un usuario y marca un timestamp
     * de invalidación global para que los access tokens emitidos antes
     * de este momento sean rechazados en validateAccessToken().
     * Útil en: cambio de password, "cerrar todas las sesiones".
     */
    public function revokeAllUserTokens(string $userId): void
    {
        $listKey = "user_tokens:{$userId}";
        $tokens  = cache()->get($listKey, []);

        foreach ($tokens as $tokenData) {
            cache()->forget($this->refreshTokenKey($tokenData['token']));
        }

        cache()->forget($listKey);

        // Guardar timestamp de invalidación global.
        // validateAccessToken rechazará cualquier access token cuyo iat
        // sea anterior o igual a este momento.
        // TTL = duración máxima del access token (15 min) — después ya
        // estarían expirados por su propia exp y este check sería redundante.
        cache()->put(
            "user_invalidated_at:{$userId}",
            time(),
            now()->addMinutes(self::ACCESS_TOKEN_TTL_MINUTES)
        );
    }

    /**
     * Revoca un refresh token específico (logout de un dispositivo).
     */
    public function revokeRefreshToken(string $token): void
    {
        cache()->forget($this->refreshTokenKey($token));
    }

    // ═══════════════════════════════════════════════════════
    // HELPERS PRIVADOS
    // ═══════════════════════════════════════════════════════

    private function refreshTokenKey(string $token): string
    {
        // Hasheamos el token para no guardarlo en texto plano en Redis
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

        // Limpiar tokens viejos de la lista antes de agregar
        $list = array_filter($list, fn($t) => $t['expires_at'] > now()->timestamp);

        $list[] = [
            'token'      => hash('sha256', $token), // Solo guardamos el hash
            'expires_at' => $expiresAt->timestamp,
        ];

        cache()->put($listKey, array_values($list), $expiresAt);
    }
}
