<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Data\Users\UpdateUserData;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryExtendedInterface;
use Spatie\LaravelData\Optional;

// ═══════════════════════════════════════════════════════════
// UpdateUserAction
// ═══════════════════════════════════════════════════════════

class UpdateUserAction
{
    public function __construct(
        private readonly UserRepositoryExtendedInterface $userRepository,
    ) {}

    public function __invoke(User $user, UpdateUserData $data, User $updatedBy): User
    {

        $fields = $data->toArray();

        // Email: solo se actualiza si el updater tiene permiso
        if (isset($fields['email']) && ! $updatedBy->hasPermission('users:update')) {
            unset($fields['email']);
        }

        $allowedFields = array_filter($fields, fn($v) => $v !== null);

        return $this->userRepository->update($user, $allowedFields);
    }
}
