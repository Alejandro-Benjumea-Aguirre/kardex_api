<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TokenService;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Exceptions\Auth\{TokenExpiredException, TokenInvalidException};
use Symfony\Component\HttpFoundation\Response;

// ═══════════════════════════════════════════════════════════
// JwtAuthMiddleware
//
// CONCEPTO: ¿Qué hace un middleware de autenticación?
// ═══════════════════════════════════════════════════════════
//
// Un middleware es código que se ejecuta ANTES de que el
// request llegue al Controller. Es como un portero.
//
// Este middleware hace:
//   1. Leer el token del header Authorization: Bearer {token}
//   2. Validar el JWT (firma + expiración)
//   3. Cargar el usuario desde la DB
//   4. Inyectar el usuario en el request para que el Controller
//      lo pueda usar con auth()->user() o $request->user()
//   5. Si algo falla: devolver 401 antes de llegar al Controller
//
// SE REGISTRA EN bootstrap/app.php:
//   ->withMiddleware(function (Middleware $middleware) {
//       $middleware->alias(['auth.jwt' => JwtAuthMiddleware::class]);
//   })
//
// SE USA EN LAS RUTAS:
//   Route::middleware('auth.jwt')->group(fn() => ...);
// ═══════════════════════════════════════════════════════════

class JwtAuthMiddleware
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        // ─── PASO 1: Extraer el token del header ──────────
        //
        // El cliente envía: Authorization: Bearer eyJhbGci...
        // bearerToken() extrae todo lo que viene después de "Bearer "
        $token = $request->bearerToken();

        if (! $token) {
            return $this->unauthorized('No se proporcionó token de autenticación.', 'NO_TOKEN');
        }

        // ─── PASO 2: Validar el JWT ────────────────────────
        try {
            $payload = $this->tokenService->validateAccessToken($token);

        } catch (TokenExpiredException $e) {
            // Código específico para token expirado
            // El cliente sabe que puede intentar renovar con refresh token
            return $this->unauthorized($e->getMessage(), 'TOKEN_EXPIRED');

        } catch (TokenInvalidException $e) {
            return $this->unauthorized($e->getMessage(), 'TOKEN_INVALID');
        }

        // ─── PASO 3: Cargar el usuario ────────────────────
        //
        // ¿Por qué cargamos el usuario de la DB si ya está en el JWT?
        //
        // Porque el JWT puede tener hasta 15 minutos de antigüedad.
        // En ese tiempo el usuario pudo ser:
        //   - Desactivado (is_active = false)
        //   - Borrado (deleted_at != null)
        //
        // Verificar el usuario en DB garantiza que el estado es actual.
        // Si el sistema tiene millones de requests, podés cachear
        // el usuario en Redis por 1-2 minutos para no golpear DB.
        $user = $this->userRepository->findById($payload->sub);

        if (! $user || ! $user->isActive()) {
            return $this->unauthorized('La cuenta no existe o está desactivada.', 'ACCOUNT_INACTIVE');
        }

        // ─── PASO 4: Inyectar en el request ───────────────
        //
        // auth()->user() funciona gracias a esto.
        // También guardamos el payload completo del JWT para
        // que el Controller pueda acceder a branch_id, permissions, etc.
        // sin hacer queries adicionales.
        auth()->setUser($user);

        // Guardar datos extra del token como atributos del request
        $request->attributes->set('jwt_payload',    $payload);
        $request->attributes->set('current_branch', $payload->branch_id);
        $request->attributes->set('jwt_jti',        $payload->jti);
        $request->attributes->set('jwt_exp',        $payload->exp);

        return $next($request);
    }

    private function unauthorized(string $message, string $code): Response
    {
        return response()->json([
            'success' => false,
            'error'   => [
                'code'    => $code,
                'message' => $message,
            ],
        ], 401);
    }
}
