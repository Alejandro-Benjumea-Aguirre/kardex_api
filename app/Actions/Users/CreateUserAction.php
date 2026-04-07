<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Data\Users\CreateUserData;
use App\Models\{User, Role};
use App\Repositories\Interfaces\{UserRepositoryExtendedInterface, RoleRepositoryInterface};
use App\Services\EmailService;
use App\Exceptions\Users\UserNotFoundException;
use App\Exceptions\Roles\{RoleNotFoundException, CannotModifySystemRoleException, UserAlreadyHasRoleException};

// ═══════════════════════════════════════════════════════════
// CreateUserAction
// ═══════════════════════════════════════════════════════════

class CreateUserAction
{
    public function __construct(
        private readonly UserRepositoryExtendedInterface $userRepository,
        private readonly RoleRepositoryInterface         $roleRepository,
        private readonly EmailService                    $emailService,
    ) {}

    public function __invoke(CreateUserData $data, User $createdBy): User
    {
        // Crear el usuario
        $user = $this->userRepository->create([
            'company_id'        => $data->company_id ?? $createdBy->company_id,
            'first_name'        => $data->first_name,
            'last_name'         => $data->last_name,
            'email'             => $data->email,
            'password'          => $data->password,
            'phone'             => $data->phone,
            'is_active'         => true,
            'is_email_verified' => false,
        ]);

        // Si viene un role_id, asignar el rol inicial
        if ($data->role_id) {
            $role = $this->roleRepository->findById($data->role_id);

            if ($role) {
                $this->userRepository->assignRole(
                    user:       $user,
                    role:       $role,
                    branchId:   $data->branch_id,
                    assignedBy: $createdBy->id,
                );
            }
        } else {
            // Asignar rol default de la empresa si existe
            $defaultRole = $this->roleRepository->findByName('cashier');
            if ($defaultRole) {
                $this->userRepository->assignRole(
                    user:       $user,
                    role:       $defaultRole,
                    branchId:   $data->branch_id,
                    assignedBy: $createdBy->id,
                );
            }
        }

        // Enviar email de bienvenida con instrucciones para verificar
        $verificationToken = bin2hex(random_bytes(32));
        $this->userRepository->setEmailVerificationToken($user, $verificationToken);
        $this->emailService->sendVerificationEmail($user, $verificationToken);

        return $user->fresh(['company', 'roles']);
    }
}
