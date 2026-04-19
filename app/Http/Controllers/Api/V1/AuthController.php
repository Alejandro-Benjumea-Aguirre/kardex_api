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

class AuthController extends Controller
{
    // ─── POST /api/v1/auth/login ──────────────────────────
    public function login(LoginRequest $request, LoginAction $action): JsonResponse
    {
        try {
            $result = $action(LoginData::from($request));

            $response = AuthResource::fromLoginResult($result);

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
            $result = $action(RegisterData::fromRequest($request->all()));

            return response()->json([
                'success' => true,
                'message' => 'Registro exitoso.',
                'data'    => new UserResource($result['user']),
            ], 201);

        } catch (AuthException $e) {
            return $this->authError($e);
        }
    }

    // ─── POST /api/v1/auth/refresh ────────────────────────
    public function refresh(Request $request, RefreshTokenAction $action): JsonResponse
    {

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
    public function logout(Request $request, LogoutAction $action): JsonResponse
    {
        $refreshToken = $request->cookie('refresh_token')
                     ?? $request->input('refresh_token', '');

        $action(
            jti:          $request->attributes->get('jwt_jti'),
            exp:          $request->attributes->get('jwt_exp'),
            refreshToken: $refreshToken,
        );

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente.',
        ])->withoutCookie('refresh_token');
    }

    // ─── POST /api/v1/auth/logout-all ─────────────────────
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
