<?php

declare(strict_types=1);

namespace App\Actions\Products;

use App\Models\{Products};
use App\Repositories\Interfaces\{ProductsRepositoryExtendedInterface, RoleRepositoryInterface};
use App\Exceptions\Users\ProductsNotFoundException;
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException, UserAlreadyHasRoleException};

// ═══════════════════════════════════════════════════════════
// DeactivateUserAction
// ═══════════════════════════════════════════════════════════

class DeactivateProductsAction
{
    public function __construct(
        private readonly ProductsRepositoryExtendedInterface $productsRepository,
    ) {}

    public function __invoke(Products $product, Products $deactivatedBy): void
    {
        $this->productsRepository->deactivate($product);
    }
}
