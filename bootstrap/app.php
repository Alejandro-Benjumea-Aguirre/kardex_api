<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use App\Http\Middleware\JwtAuthMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Exceptions\Auth\AuthException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.jwt'   => JwtAuthMiddleware::class,
            'permission' => PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (AuthException $e) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => $e->errorCode(),
                    'message' => $e->getMessage(),
                ],
            ], $e->httpStatus());
        });

        $exceptions->render(function (ThrottleRequestsException $e) {
            return response()->json([
                'success' => false,
                'error'   => [
                    'code'    => 'TOO_MANY_REQUESTS',
                    'message' => 'Demasiadas solicitudes. Intentá de nuevo en ' . $e->getHeaders()['Retry-After'] . ' segundos.',
                ],
            ], 429)->withHeaders($e->getHeaders());
        });
    })->create();
