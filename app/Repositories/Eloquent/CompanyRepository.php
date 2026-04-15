<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Company;
use App\Repositories\Interfaces\CompanyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CompanyRepository implements CompanyRepositoryInterface
{
    // ─── BÚSQUEDAS ───────────────────────────────────────

    public function findById(string $id): ?Company
    {
        return Company::find($id);
    }

    public function findBySlug(string $slug): ?Company
    {
        return Company::where('slug', $slug)->first();
    }

    public function findByName(string $name): ?Company
    {
        return Company::where('name', $name)->first();
    }

    public function all(): Collection
    {
        return Company::active()->get();
    }

    // ─── ESCRITURA ────────────────────────────────────────

    public function create(array $data): Company
    {
        return Company::create($data);
    }

    public function update(Company $company, array $data): Company
    {
        $company->update($data);

        return $company->fresh();
    }

    public function delete(Company $company): bool
    {
        return $company->delete();
    }

    // ─── CONFIGURACIÓN ────────────────────────────────────

    public function updateSettings(Company $company, array $settings): Company
    {
        $company->update([
            'settings' => array_merge($company->settings, $settings),
        ]);

        return $company->fresh();
    }

    public function updatePlanLimits(Company $company, array $limits): Company
    {
        $company->update([
            'plan_limits' => array_merge($company->plan_limits, $limits),
        ]);

        return $company->fresh();
    }

    public function updateLogo(Company $company, string $logoUrl): Company
    {
        $company->update(['logo_url' => $logoUrl]);

        return $company->fresh();
    }

    // ─── PLAN ─────────────────────────────────────────────

    public function changePlan(Company $company, string $plan): Company
    {
        $limits = match ($plan) {
            'free'         => ['max_branches' => 1,  'max_users' => 3,   'max_products' => 100],
            'starter'      => ['max_branches' => 2,  'max_users' => 10,  'max_products' => 500],
            'professional' => ['max_branches' => 5,  'max_users' => 25,  'max_products' => 2000],
            'enterprise'   => ['max_branches' => -1, 'max_users' => -1,  'max_products' => -1], // -1 = ilimitado
        };

        $company->update([
            'plan'        => $plan,
            'plan_limits' => $limits,
        ]);

        return $company->fresh();
    }

    // ─── ESTADO ──────────────────────────────────────────

    public function activate(Company $company): Company
    {
        $company->update(['is_active' => true]);

        return $company->fresh();
    }

    public function deactivate(Company $company): Company
    {
        $company->update(['is_active' => false]);

        return $company->fresh();
    }

    // ─── LÍMITES ─────────────────────────────────────────

    public function hasReachedUserLimit(Company $company): bool
    {
        // -1 significa ilimitado
        if ($company->max_users === -1) {
            return false;
        }

        return $company->users()->count() >= $company->max_users;
    }

    public function hasReachedProductLimit(Company $company): bool
    {
        // -1 significa ilimitado
        if ($company->max_products === -1) {
            return false;
        }

        return $company->products()->count() >= $company->max_products;
    }
}