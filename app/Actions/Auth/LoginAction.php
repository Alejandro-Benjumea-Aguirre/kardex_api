<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Data\Auth\{AuthResultData, LoginData};
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\TokenService;
use App\Exceptions\Auth\{
    InvalidCredentialsException,
    AccountInactiveException,
    EmailNotVerifiedException,
    TooManyAttemptsException
};

// ═══════════════════════════════════════════════════════════
// LoginAction
//
// CONCEPTO: Single Action Class — un caso de uso, una clase
// ═══════════════════════════════════════════════════════════
//
// Esta clase hace UNA SOLA COSA: autenticar al usuario.
// Nada más. Si mañana necesitás login con Google OAuth,
// creás GoogleLoginAction — no modificás esta.
//
// El método __invoke() permite llamar la clase como función:
//   app(LoginAction::class)(LoginData::from($request))
//
// FLUJO COMPLETO DEL LOGIN:
//   1. Buscar usuario por email
//   2. Verificar que existe y está activo
//   3. Verificar que el email está verificado
//   4. Verificar intentos fallidos (anti brute force)
//   5. Verificar el password
//   6. Si todo OK: resetear intentos fallidos
//   7. Actualizar last_login_at
//   8. Cargar y cachear permisos para la sucursal
//   9. Generar access token + refresh token
//   10. Devolver resultado
// ═══════════════════════════════════════════════════════════

class LoginAction
{
    // Máximo de intentos fallidos antes de bloquear
    private const MAX_FAILED_ATTEMPTS = 5;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly TokenService $tokenService,
    ) {}

    /**
     * @throws InvalidCredentialsException
     * @throws AccountInactiveException
     * @throws EmailNotVerifiedException
     * @throws TooManyAttemptsException
     */
    public function __invoke(LoginData $data): AuthResultData
    {
        $branchId = $data->branch_id ?? '';

        // ─── PASO 1: Buscar el usuario ────────────────────
        $user = $this->userRepository->findByEmail($data->email);

        // IMPORTANTE: si el usuario no existe, respondemos igual
        // que si el password fuera incorrecto.
        // Nunca digas "el email no existe" — eso le dice al atacante
        // qué emails están registrados en el sistema.
        if (! $user) {
            throw new InvalidCredentialsException();
        }

        // ─── PASO 2: Verificar estado de la cuenta ────────
        if (! $user->isActive()) {
            throw new AccountInactiveException();
        }

        // ─── PASO 3: Verificar email verificado ───────────
        // En producción descomentar esta validación:
        // if (! $user->isEmailVerified()) {
        //     throw new EmailNotVerifiedException();
        // }

        // ─── PASO 4: Anti brute force ─────────────────────
        //
        // Si el usuario tuvo demasiados intentos fallidos,
        // bloqueamos ANTES de verificar el password.
        // Así el atacante no puede ni intentar más passwords.
        $failedAttempts = $this->userRepository->getFailedLoginCount($user);

        if ($failedAttempts >= self::MAX_FAILED_ATTEMPTS) {
            throw new TooManyAttemptsException(
                'Demasiados intentos fallidos. Intentá en 15 minutos.'
            );
        }

        // ─── PASO 5: Verificar password ───────────────────
        //
        // Hash::check() compara el password plano con el hash bcrypt.
        // bcrypt es slow-by-design: en una PC moderna tarda ~100ms.
        // Eso hace que un ataque de diccionario sea imprácticamente lento.
        //
        // NUNCA compares passwords con == o ===.
        // NUNCA hashees el password antes de comparar (como MD5).
        // SOLO usá Hash::check() o password_verify().
        if (! \Hash::check($data->password, $user->password)) {
            // Password incorrecto → incrementar contador de fallos
            $this->userRepository->incrementFailedLogins($user);
            throw new InvalidCredentialsException();
        }

        // ─── PASO 6 y 7: Login exitoso ────────────────────
        $this->userRepository->resetFailedLogins($user);
        $this->userRepository->updateLastLogin($user);

        // ─── PASO 8: Cargar y cachear permisos ────────────
        //
        // Cargamos los permisos del usuario para esta sucursal
        // y los guardamos en Redis. El access token los incluirá
        // directamente en su payload para que el middleware
        // no necesite consultar Redis en cada request.
        $permissions = $user->loadAndCachePermissions($branchId);

        // ─── PASO 9: Generar tokens ───────────────────────
        $accessToken  = $this->tokenService->generateAccessToken($user, $branchId);
        $refreshToken = $this->tokenService->generateRefreshToken($user, $branchId);

        // ─── PASO 10: Devolver resultado ──────────────────
        return new AuthResultData(
            user:          $user->load(['company', 'roles']),
            access_token:  $accessToken,
            refresh_token: $refreshToken,
            token_type:    'Bearer',
            expires_in:    15 * 60,
        );
    }
}
