<?php

declare(strict_types=1);

namespace App\Repositories\Interfaces;

use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

// ═══════════════════════════════════════════════════════════
// CompanyRepositoryInterface

interface CompanyRepositoryInterface
{
    // ─── BÚSQUEDAS ───────────────────────────────────────
    public function findById(string $id): ?Company;
    public function findBySlug(string $slug): ?Company;
    public function findByName(string $name): ?Company;
    public function all(): Collection;

    // ─── ESCRITURA ────────────────────────────────────────
    public function create(array $data): Company;
    public function update(Company $company, array $data): Company;
    public function delete(Company $company): bool;

    // ─── CONFIGURACIÓN ────────────────────────────────────
    public function updateSettings(Company $company, array $settings): Company;
    public function updatePlanLimits(Company $company, array $limits): Company;
    public function updateLogo(Company $company, string $logoUrl): Company;

    // ─── PLAN ─────────────────────────────────────────────
    public function changePlan(Company $company, string $plan): Company;

    // ─── ESTADO ──────────────────────────────────────────
    public function activate(Company $company): Company;
    public function deactivate(Company $company): Company;

    // ─── LÍMITES ─────────────────────────────────────────
    public function hasReachedUserLimit(Company $company): bool;
    public function hasReachedProductLimit(Company $company): bool;
}