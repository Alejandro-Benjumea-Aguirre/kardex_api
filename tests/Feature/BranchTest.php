<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Branch, Company, Role, User};
use App\Http\Middleware\{JwtAuthMiddleware, PermissionMiddleware};
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    private User    $admin;
    private User    $viewer;
    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company = Company::factory()->create();

        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->admin->roles()->attach(Role::where('name', 'admin')->first()->id);

        $this->viewer = User::factory()->create(['company_id' => $this->company->id]);
        $this->viewer->roles()->attach(Role::where('name', 'viewer')->first()->id);
    }

    private function asUser(User $user): static
    {
        return $this->withoutMiddleware([JwtAuthMiddleware::class, PermissionMiddleware::class])
                    ->actingAs($user);
    }

    private function asViewer(): static
    {
        return $this->withoutMiddleware(JwtAuthMiddleware::class)
                    ->actingAs($this->viewer);
    }

    private function branchData(array $overrides = []): array
    {
        return array_merge([
            'company_id' => $this->company->id,
            'name'       => 'Sucursal Central',
            'code'       => 'SC-001',
        ], $overrides);
    }

    // ══════════════════════════════════════════════════════
    // GET /api/v1/branch
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_listar_sucursales(): void
    {
        Branch::factory(2)->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->getJson('/api/v1/branch')
             ->assertStatus(200)
             ->assertJson(['success' => true])
             ->assertJsonStructure(['data', 'meta']);
    }

    public function test_sin_autenticacion_devuelve_401(): void
    {
        $this->getJson('/api/v1/branch')->assertStatus(401);
    }

    // ══════════════════════════════════════════════════════
    // POST /api/v1/branch
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_crear_sucursal(): void
    {
        $response = $this->asUser($this->admin)
             ->postJson('/api/v1/branch', $this->branchData());

        $response->assertStatus(201)
                 ->assertJson(['success' => true, 'message' => 'Sucursal creada correctamente.'])
                 ->assertJsonStructure(['data' => ['id', 'name', 'code']]);

        $this->assertDatabaseHas('branches', [
            'company_id' => $this->company->id,
            'code'       => 'SC-001',
        ]);
    }

    public function test_crear_sucursal_requiere_campos_obligatorios(): void
    {
        $this->asUser($this->admin)
             ->postJson('/api/v1/branch', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['company_id', 'name', 'code']);
    }

    public function test_codigo_duplicado_en_misma_empresa_falla(): void
    {
        Branch::factory()->create([
            'company_id' => $this->company->id,
            'code'       => 'SC-001',
        ]);

        $this->asUser($this->admin)
             ->postJson('/api/v1/branch', $this->branchData())
             ->assertStatus(422)
             ->assertJsonValidationErrors(['code']);
    }

    public function test_viewer_no_puede_crear_sucursales(): void
    {
        $this->asViewer()
             ->postJson('/api/v1/branch', $this->branchData())
             ->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════
    // GET /api/v1/branch/{branch}
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_ver_detalle_de_sucursal(): void
    {
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->getJson("/api/v1/branch/{$branch->id}")
             ->assertStatus(200)
             ->assertJson(['success' => true, 'data' => ['id' => $branch->id]]);
    }

    public function test_sucursal_inexistente_devuelve_404(): void
    {
        $this->asUser($this->admin)
             ->getJson('/api/v1/branch/00000000-0000-0000-0000-000000000000')
             ->assertStatus(404);
    }

    // ══════════════════════════════════════════════════════
    // PUT /api/v1/branch/{branch}
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_actualizar_sucursal(): void
    {
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->putJson("/api/v1/branch/{$branch->id}", ['name' => 'Nuevo Nombre'])
             ->assertStatus(200)
             ->assertJson(['success' => true, 'message' => 'Sucursal actualizada correctamente.']);

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'Nuevo Nombre']);
    }

    public function test_viewer_no_puede_actualizar_sucursales(): void
    {
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->asViewer()
             ->putJson("/api/v1/branch/{$branch->id}", ['name' => 'Hack'])
             ->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════
    // DELETE /api/v1/branch/{branch}
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_desactivar_sucursal(): void
    {
        $branch = Branch::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->deleteJson("/api/v1/branch/{$branch->id}")
             ->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'is_active' => false]);
    }

    // ══════════════════════════════════════════════════════
    // POST /api/v1/branch/{branch}/activate
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_activar_sucursal(): void
    {
        $branch = Branch::factory()->inactive()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->postJson("/api/v1/branch/{$branch->id}/activate")
             ->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'is_active' => true]);
    }
}
