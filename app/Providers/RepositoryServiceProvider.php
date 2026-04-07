<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryExtendedInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Services\TokenService;

// ═══════════════════════════════════════════════════════════
// RepositoryServiceProvider
//
// CONCEPTO: El IoC Container (Inversión de Control)
// ═══════════════════════════════════════════════════════════
//
// Cuando un Controller o Action tiene en su constructor:
//   public function __construct(
//       private readonly UserRepositoryInterface $userRepository
//   ) {}
//
// Laravel necesita saber QUÉ CLASE concreta inyectar cuando
// alguien pide UserRepositoryInterface.
//
// Este ServiceProvider le dice al Container:
//   "Cuando alguien pida UserRepositoryInterface,
//    dale una instancia de UserRepository"
//
// Esto es lo que permite que mañana cambies la implementación
// sin tocar ningún archivo que use el repositorio:
//   Solo cambiás este binding → todo el sistema usa la nueva clase.
//
// REGISTRAR EN bootstrap/app.php:
//   ->withProviders([
//       App\Providers\RepositoryServiceProvider::class,
//   ])
// ═══════════════════════════════════════════════════════════

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

        // TokenService como singleton — una sola instancia en toda la app
        // Singleton es correcto aquí porque TokenService no tiene estado
        // mutable entre requests (usa Redis/caché para estado)
        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService(
                secret:  config('jwt.secret'),
                appName: config('app.name'),
            );
        });
    }
}
