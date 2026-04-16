<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryExtendedInterface;
use App\Repositories\Interfaces\RoleRepositoryInterface;
use App\Repositories\Interfaces\PermissionRepositoryInterface;
use App\Repositories\Interfaces\CategoryRepositoryExtendedInterface;
use App\Repositories\Interfaces\ProductsRepositoryExtendedInterface;
use App\Repositories\Eloquent\UserRepository;
use App\Repositories\Eloquent\RoleRepository;
use App\Repositories\Eloquent\PermissionRepository;
use App\Repositories\Eloquent\CategoryRepository;
use App\Repositories\Eloquent\ProductsRepository;
use App\Services\TokenService;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(UserRepositoryExtendedInterface::class, UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(PermissionRepositoryInterface::class, PermissionRepository::class);
        $this->app->bind(CategoryRepositoryExtendedInterface::class, CategoryRepository::class);
        $this->app->bind(ProductsRepositoryExtendedInterface::class, ProductsRepository::class);
        $this->app->bind(BranchRepositoryExtendedInterface::class, BrancRepository::class);
        $this->app->bind(CompanyRepositoryExtendedInterface::class, CompanyRepository::class);
        $this->app->bind(InventaryRepositoryExtendedInterface::class, InventaryRepository::class);

        $this->app->singleton(TokenService::class, function ($app) {
            return new TokenService(
                secret:  config('jwt.secret'),
                appName: config('app.name'),
            );
        });
    }
}
