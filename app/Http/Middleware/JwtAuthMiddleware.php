<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\TokenService;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Exceptions\Auth\{TokenExpiredException, TokenInvalidException};
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly UserRepositoryInterface $userRepository,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {

        $token = $request->bearerToken();

        if (! $token) {
            return $this->unauthorized('No se proporcionó token de autenticación.', 'NO_TOKEN');
        }

        try {
            $payload = $this->tokenService->validateAccessToken($token);

        } catch (TokenExpiredException $e) {

            return $this->unauthorized($e->getMessage(), 'TOKEN_EXPIRED');

        } catch (TokenInvalidException $e) {
            return $this->unauthorized($e->getMessage(), 'TOKEN_INVALID');
        }

        $user = $this->userRepository->findById($payload->sub);

        if (! $user || ! $user->isActive()) {
            return $this->unauthorized('La cuenta no existe o está desactivada.', 'ACCOUNT_INACTIVE');
        }

        auth()->setUser($user);

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
