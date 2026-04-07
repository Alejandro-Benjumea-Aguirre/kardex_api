<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryExtendedInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Services\TokenService;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Interface → Implementación concreta
        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            UserRepositoryExtendedInterface::class,
            UserRepository::class
        );

        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService(
                secret:  config('jwt.secret'),
                appName: config('app.name'),
            );
        });
    }
}
