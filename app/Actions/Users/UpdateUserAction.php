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
        // ─── CONCEPTO: Qué campos puede actualizar cada rol ───
        //
        // toArray() de Spatie Data excluye automáticamente los campos
        // Optional (los que no vinieron en el request).
        // Solo quedan los campos que el cliente envió explícitamente.
        $fields = $data->toArray();

        // Email: solo se actualiza si el updater tiene permiso
        if (isset($fields['email']) && ! $updatedBy->hasPermission('users:update')) {
            unset($fields['email']);
        }

        // Eliminar nulls (campos enviados como null que no queremos borrar)
        $allowedFields = array_filter($fields, fn($v) => $v !== null);

        return $this->userRepository->update($user, $allowedFields);
    }
}
