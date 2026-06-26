<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Category, Company, Role, User};
use App\Http\Middleware\{JwtAuthMiddleware, PermissionMiddleware};
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
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

    private function categoryData(array $overrides = []): array
    {
        return array_merge([
            'company_id'  => $this->company->id,
            'name'        => 'Electrónica',
            'slug'        => 'electronica-001',
            'description' => 'Productos electrónicos',
            'image_url'   => null,
        ], $overrides);
    }

    // ══════════════════════════════════════════════════════
    // GET /api/v1/category
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_listar_categorias(): void
    {
        Category::factory(3)->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->getJson('/api/v1/category')
             ->assertStatus(200)
             ->assertJson(['success' => true])
             ->assertJsonStructure(['data', 'meta']);
    }

    public function test_sin_autenticacion_devuelve_401(): void
    {
        $this->getJson('/api/v1/category')->assertStatus(401);
    }

    // ══════════════════════════════════════════════════════
    // POST /api/v1/category
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_crear_categoria(): void
    {
        $response = $this->asUser($this->admin)
             ->postJson('/api/v1/category', $this->categoryData());

        $response->assertStatus(201)
                 ->assertJson(['success' => true, 'message' => 'Categoria creada correctamente.'])
                 ->assertJsonStructure(['data' => ['id', 'name']]);

        $this->assertDatabaseHas('categories', [
            'company_id' => $this->company->id,
            'name'       => 'Electrónica',
        ]);
    }

    public function test_crear_categoria_requiere_campos_obligatorios(): void
    {
        $this->asUser($this->admin)
             ->postJson('/api/v1/category', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['name', 'slug', 'description']);
    }

    public function test_viewer_no_puede_crear_categorias(): void
    {
        $this->asViewer()
             ->postJson('/api/v1/category', $this->categoryData())
             ->assertStatus(403);
    }

    public function test_se_puede_crear_subcategoria(): void
    {
        $parent = Category::factory()->create(['company_id' => $this->company->id]);

        $response = $this->asUser($this->admin)
             ->postJson('/api/v1/category', $this->categoryData([
                 'name'      => 'Smartphones',
                 'slug'      => 'smartphones-001',
                 'parent_id' => $parent->id,
             ]));

        $response->assertStatus(201);
        $this->assertDatabaseHas('categories', ['parent_id' => $parent->id, 'name' => 'Smartphones']);
    }

    // ══════════════════════════════════════════════════════
    // GET /api/v1/category/{category}
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_ver_detalle_de_categoria(): void
    {
        $category = Category::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->getJson("/api/v1/category/{$category->id}")
             ->assertStatus(200)
             ->assertJson(['success' => true, 'data' => ['id' => $category->id]]);
    }

    public function test_categoria_inexistente_devuelve_404(): void
    {
        $this->asUser($this->admin)
             ->getJson('/api/v1/category/00000000-0000-0000-0000-000000000000')
             ->assertStatus(404);
    }

    // ══════════════════════════════════════════════════════
    // PUT /api/v1/category/{category}
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_actualizar_categoria(): void
    {
        $category = Category::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->putJson("/api/v1/category/{$category->id}", [
                 'name' => 'Nombre Actualizado',
                 'slug' => 'nombre-actualizado',
             ])
             ->assertStatus(200)
             ->assertJson(['success' => true, 'message' => 'Categoria actualizada correctamente.']);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Nombre Actualizado']);
    }

    public function test_viewer_no_puede_actualizar_categorias(): void
    {
        $category = Category::factory()->create(['company_id' => $this->company->id]);

        $this->asViewer()
             ->putJson("/api/v1/category/{$category->id}", ['name' => 'Hack', 'slug' => 'hack'])
             ->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════
    // DELETE /api/v1/category/{category}
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_desactivar_categoria(): void
    {
        $category = Category::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->deleteJson("/api/v1/category/{$category->id}")
             ->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_active' => false]);
    }

    // ══════════════════════════════════════════════════════
    // POST /api/v1/category/{category}/activate
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_activar_categoria(): void
    {
        $category = Category::factory()->inactive()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->postJson("/api/v1/category/{$category->id}/activate")
             ->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'is_active' => true]);
    }

    // ══════════════════════════════════════════════════════
    // GET /api/v1/category/{category}/subcategories
    // ══════════════════════════════════════════════════════

    public function test_puede_listar_subcategorias(): void
    {
        $parent = Category::factory()->create(['company_id' => $this->company->id]);
        Category::factory(2)->create([
            'company_id' => $this->company->id,
            'parent_id'  => $parent->id,
        ]);

        $response = $this->asUser($this->admin)
             ->getJson("/api/v1/category/{$parent->id}/subcategories");

        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        $this->assertCount(2, $response->json('data'));
    }
}
