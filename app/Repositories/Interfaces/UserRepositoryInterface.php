<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\User;

// ═══════════════════════════════════════════════════════════
// UserRepositoryInterface

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
