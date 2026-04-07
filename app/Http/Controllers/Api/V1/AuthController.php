<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\{JsonResponse, Request};
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\{
    LoginRequest,
    RegisterRequest,
    RefreshTokenRequest,
    ForgotPasswordRequest,
    ResetPasswordRequest,
};
use App\Http\Resources\{UserResource, AuthResource};
use App\Data\Auth\{LoginData, RegisterData, ForgotPasswordData, ResetPasswordData};
use App\Actions\Auth\{
    LoginAction,
    RegisterAction,
    LogoutAction,
    LogoutAllAction,
    RefreshTokenAction,
    ForgotPasswordAction,
    ResetPasswordAction,
    VerifyEmailAction,
};
use App\Exceptions\Auth\AuthException;

// ═══════════════════════════════════════════════════════════
// AuthController
//
// CONCEPTO: El Controller debe ser lo más delgado posible
// ═══════════════════════════════════════════════════════════
//
// RESPONSABILIDADES DEL CONTROLLER (y solo estas):
//   1. Recibir el request validado del Form Request
//   2. Extraer los datos necesarios
//   3. Llamar a la Action correspondiente
//   4. Devolver la respuesta usando el Resource
//   5. Manejar excepciones de la Action → respuesta de error
//
// El Controller NO debe:
//   ❌ Contener lógica de negocio (eso es de la Action)
//   ❌ Hablar directamente con la DB (eso es del Repository)
//   ❌ Formatear el JSON manualmente (eso es del Resource)
//
// UN MÉTODO DEL CONTROLLER = 5-15 LÍNEAS. Si tiene más, algo está mal.
//
// PATRÓN HttpOnly Cookie para refresh token:
// El refresh token se envía como cookie HttpOnly para que
// JavaScript no pueda accederlo (protección contra XSS).
// La cookie se llama 'refresh_token' y se setea en cada login/refresh.
// En el logout se borra la cookie.
// ═══════════════════════════════════════════════════════════

class AuthController extends Controller
{
    // ─── POST /api/v1/auth/login ──────────────────────────
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        try {
            $result = $action(LoginData::from($request));

            // Generar la respuesta con los datos del usuario y el access token
            $response = AuthResource::fromLoginResult($result);

            // El refresh token va en una cookie HttpOnly, NO en el body.
            // HttpOnly: JavaScript no puede leerla → protegida de XSS
            // Secure: solo se envía por HTTPS (en producción)
            // SameSite: Lax protege contra CSRF básico
            return $response->withCookie(
                cookie(
                    name:     'refresh_token',
                    value:    $result->refresh_token,
                    minutes:  30 * 24 * 60,
                    secure:   app()->isProduction(),
                    httpOnly: true,
                    sameSite: 'Lax',
                )
            );

        } catch (AuthException $e) {
            return $this->authError($e);
        }
    }

    // ─── POST /api/v1/auth/register ───────────────────────
    public function register(RegisterRequest $request, RegisterAction $action): JsonResponse
    {
        try {
            $user = $action(RegisterData::from($request)->user);

            return response()->json([
                'success' => true,
                'message' => 'Registro exitoso. Revisá tu email para verificar tu cuenta.',
                'data'    => new UserResource($user),
            ], 201);

        } catch (AuthException $e) {
            return $this->authError($e);
        }
    }

    // ─── POST /api/v1/auth/refresh ────────────────────────
    public function refresh(Request $request, RefreshTokenAction $action): JsonResponse
    {
        // El refresh token viene de la cookie HttpOnly
        // Si el cliente es una app móvil, puede venir en el body también
        $refreshToken = $request->cookie('refresh_token')
                     ?? $request->input('refresh_token');

        if (! $refreshToken) {
            return response()->json([
                'success' => false,
                'error'   => ['code' => 'NO_REFRESH_TOKEN', 'message' => 'No se proporcionó refresh token.'],
            ], 401);
        }

        try {
            $result = $action($refreshToken);

            return AuthResource::fromRefreshResult($result)
                ->withCookie(
                    cookie(
                        name:     'refresh_token',
                        value:    $result->refresh_token,
                        minutes:  30 * 24 * 60,
                        secure:   app()->isProduction(),
                        httpOnly: true,
                        sameSite: 'Lax',
                    )
                );

        } catch (AuthException $e) {
            return $this->authError($e);
        }
    }

    // ─── POST /api/v1/auth/logout ─────────────────────────
    // Requiere middleware auth.jwt
    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $refreshToken = $request->cookie('refresh_token')
                     ?? $request->input('refresh_token', '');

        $action(
            jti:          $request->attributes->get('jwt_jti'),
            exp:          $request->attributes->get('jwt_exp'),
            refreshToken: $refreshToken,
        );

        // Borrar la cookie del cliente
        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ])->withoutCookie('refresh_token');
    }

    // ─── POST /api/v1/auth/logout-all ─────────────────────
    // Cierra todas las sesiones en todos los dispositivos
    public function logoutAll(Request $request, LogoutAllAction $action): JsonResponse
    {
        $action(
            user: $request->user(),
            jti:  $request->attributes->get('jwt_jti'),
            exp:  $request->attributes->get('jwt_exp'),
        );

        return response()->json([
            'success' => true,
            'message' => 'Todas las sesiones fueron cerradas.',
        ])->withoutCookie('refresh_token');
    }

    // ─── POST /api/v1/auth/forgot-password ────────────────
    public function forgotPassword(
        ForgotPasswordRequest $request,
        ForgotPasswordAction  $action,
    ): JsonResponse {
        // La Action no lanza excepción si el email no existe
        // — la respuesta es siempre la misma para no revelar
        // qué emails están registrados
        $action(ForgotPasswordData::from($request)->email);

        return response()->json([
            'success' => true,
            'message' => 'Si tu email está registrado, recibirás las instrucciones en unos minutos.',
        ]);
    }

    // ─── POST /api/v1/auth/reset-password ─────────────────
    public function resetPassword(
        ResetPasswordRequest $request,
        ResetPasswordAction  $action,
    ): JsonResponse {
        try {
            $action(ResetPasswordData::from($request));

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente. Iniciá sesión de nuevo.',
            ]);

        } catch (AuthException $e) {
            return $this->authError($e);
        }
    }

    // ─── GET /api/v1/auth/verify-email/{id}/{token} ───────
    public function verifyEmail(
        string            $id,
        string            $token,
        VerifyEmailAction $action,
    ): JsonResponse {
        try {
            $action($id, $token);

            return response()->json([
                'success' => true,
                'message' => 'Email verificado correctamente. Ya podés iniciar sesión.',
            ]);

        } catch (AuthException $e) {
            return $this->authError($e);
        }
    }

    // ─── GET /api/v1/auth/me ──────────────────────────────
    // Devuelve los datos del usuario autenticado
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new UserResource(
                $request->user()->load(['company', 'roles'])
            ),
        ]);
    }

    // ─── Helper: respuesta de error de autenticación ──────
    private function authError(AuthException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error'   => [
                'code'    => $e->errorCode(),
                'message' => $e->getMessage(),
            ],
        ], $e->httpStatus());
    }
}
