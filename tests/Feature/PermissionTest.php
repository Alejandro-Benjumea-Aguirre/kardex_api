<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Company, Role, User};
use App\Http\Middleware\JwtAuthMiddleware;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $company = Company::factory()->create();

        $adminRole   = Role::where('name', 'admin')->first();
        $cashierRole = Role::where('name', 'cashier')->first();

        $this->admin = User::factory()->create(['company_id' => $company->id]);
        $this->admin->roles()->attach($adminRole->id);

        // cashier NO tiene roles:read → no puede ver permisos
        $this->cashier = User::factory()->create(['company_id' => $company->id]);
        $this->cashier->roles()->attach($cashierRole->id);
    }

    // ─── HELPER ────────────────────────────────────────────

    private function asUser(User $user): static
    {
        return $this->withoutMiddleware(JwtAuthMiddleware::class)
                    ->actingAs($user);
    }

    // ═══════════════════════════════════════════════════════
    // GET /api/v1/permissions
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_listar_todos_los_permisos(): void
    {
        $response = $this->asUser($this->admin)
            ->getJson('/api/v1/permissions');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'data' => [['id', 'name', 'display_name', 'module', 'is_system']],
                 ]);

        // El seeder crea más de 10 permisos
        $this->assertGreaterThan(10, count($response->json('data')));
    }

    public function test_todos_los_permisos_tienen_formato_modulo_accion(): void
    {
        $response = $this->asUser($this->admin)
            ->getJson('/api/v1/permissions');

        $response->assertStatus(200);

        foreach ($response->json('data') as $permission) {
            $this->assertStringContainsString(':', $permission['name'],
                "Permiso '{$permission['name']}' no tiene formato módulo:acción"
            );
        }
    }

    public function test_cashier_no_puede_listar_permisos(): void
    {
        $this->asUser($this->cashier)
             ->getJson('/api/v1/permissions')
             ->assertStatus(403)
             ->assertJson(['error' => ['code' => 'INSUFFICIENT_PERMISSIONS']]);
    }

    public function test_sin_autenticacion_devuelve_401(): void
    {
        $this->getJson('/api/v1/permissions')->assertStatus(401);
    }

    // ═══════════════════════════════════════════════════════
    // GET /api/v1/permissions/by-module
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_listar_permisos_por_modulo(): void
    {
        $response = $this->asUser($this->admin)
            ->getJson('/api/v1/permissions/by-module');

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertNotEmpty($data);
    }

    public function test_by_module_incluye_modulos_principales(): void
    {
        $response = $this->asUser($this->admin)
            ->getJson('/api/v1/permissions/by-module');

        $response->assertStatus(200);

        $modules = array_keys($response->json('data'));

        foreach (['products', 'sales', 'users', 'roles'] as $expected) {
            $this->assertContains($expected, $modules,
                "Falta el módulo '{$expected}' en la respuesta"
            );
        }
    }

    public function test_cada_modulo_tiene_al_menos_un_permiso(): void
    {
        $response = $this->asUser($this->admin)
            ->getJson('/api/v1/permissions/by-module');

        $response->assertStatus(200);

        foreach ($response->json('data') as $module => $permissions) {
            $this->assertNotEmpty($permissions,
                "El módulo '{$module}' no tiene permisos"
            );
        }
    }

    public function test_by_module_cada_permiso_tiene_campos_requeridos(): void
    {
        $response = $this->asUser($this->admin)
            ->getJson('/api/v1/permissions/by-module');

        $response->assertStatus(200);

        foreach ($response->json('data') as $module => $permissions) {
            foreach ($permissions as $permission) {
                $this->assertArrayHasKey('id', $permission);
                $this->assertArrayHasKey('name', $permission);
                $this->assertArrayHasKey('display_name', $permission);
            }
        }
    }

    public function test_cashier_no_puede_ver_permisos_por_modulo(): void
    {
        $this->asUser($this->cashier)
             ->getJson('/api/v1/permissions/by-module')
             ->assertStatus(403);
    }
}
