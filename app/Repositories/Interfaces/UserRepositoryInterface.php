<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\User;

// ═══════════════════════════════════════════════════════════
// UserRepositoryInterface
//
// CONCEPTO: ¿Para qué sirve una Interface en el Repository?
// ═══════════════════════════════════════════════════════════
//
// Esta interface define EL CONTRATO — qué métodos debe tener
// cualquier implementación del repositorio de usuarios.
//
// Hoy tenemos UserRepository que usa Eloquent.
// Mañana podría existir UserApiRepository que consulta una API.
// El resto del código (Actions, Services) no sabe cuál usa.
// Solo sabe que puede llamar findByEmail(), create(), etc.
//
// BENEFICIOS CONCRETOS:
//
// 1. TESTABILIDAD: En los tests podés usar un MockUserRepository
//    que devuelve datos falsos sin tocar la DB real.
//
// 2. FLEXIBILIDAD: Si cambiás de Eloquent a Doctrine, solo
//    escribís una nueva clase que implementa esta interface.
//    Las Actions no cambian ni una línea.
//
// 3. DOCUMENTACIÓN VIVA: La interface documenta qué operaciones
//    existen sobre usuarios. Es el índice del repositorio.
//
// REGISTRO EN EL SERVICE CONTAINER:
//   En RepositoryServiceProvider hacés el binding:
//   $this->app->bind(UserRepositoryInterface::class, UserRepository::class)
//   Así el IoC Container de Laravel inyecta el Eloquent repo automáticamente.
// ═══════════════════════════════════════════════════════════

interface UserRepositoryInterface
{
    // ─── BÚSQUEDAS ───────────────────────────────────────
    public function findById(string $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findByEmailVerificationToken(string $token): ?User;
    public function findByPasswordResetToken(string $token): ?User;

    // ─── ESCRITURA ────────────────────────────────────────
    public function create(array $data): User;
    public function update(User $user, array $data): User;

    // ─── TOKENS DE VERIFICACIÓN ───────────────────────────
    public function setEmailVerificationToken(User $user, string $token): void;
    public function markEmailAsVerified(User $user): void;
    public function setPasswordResetToken(User $user, string $token, \DateTimeInterface $expiresAt): void;
    public function clearPasswordResetToken(User $user): void;

    // ─── ESTADO ──────────────────────────────────────────
    public function updateLastLogin(User $user): void;
    public function incrementFailedLogins(User $user): void;
    public function resetFailedLogins(User $user): void;
    public function getFailedLoginCount(User $user): int;
}
