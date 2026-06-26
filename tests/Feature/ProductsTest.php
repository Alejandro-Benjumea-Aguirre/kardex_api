<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Category, Company, Products, Role, User};
use App\Http\Middleware\{JwtAuthMiddleware, PermissionMiddleware};
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    private User    $admin;
    private User    $viewer;
    private Company $company;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->company  = Company::factory()->create();
        $this->category = Category::factory()->create(['company_id' => $this->company->id]);

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

    private function productData(array $overrides = []): array
    {
        return array_merge([
            'name'        => 'Producto Test',
            'sku'         => 'SKU-TEST-001',
            'slug'        => 'producto-test-001',
            'description' => 'Descripción del producto test',
            'type'        => 'physical',
            'sale_price'  => 19990,
            'cost_price'  => 9990,
            'category_id' => $this->category->id,
        ], $overrides);
    }

    // ══════════════════════════════════════════════════════
    // GET /api/v1/products
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_listar_productos(): void
    {
        Products::factory(3)->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->getJson('/api/v1/products')
             ->assertStatus(200)
             ->assertJson(['success' => true])
             ->assertJsonStructure(['data', 'meta']);
    }

    public function test_sin_autenticacion_devuelve_401(): void
    {
        $this->getJson('/api/v1/products')->assertStatus(401);
    }

    // ══════════════════════════════════════════════════════
    // POST /api/v1/products
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_crear_producto(): void
    {
        $response = $this->asUser($this->admin)
             ->postJson('/api/v1/products', $this->productData());

        $response->assertStatus(201)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure(['data' => ['id', 'name', 'sku']]);

        $this->assertDatabaseHas('products', [
            'company_id' => $this->company->id,
            'sku'        => 'SKU-TEST-001',
        ]);
    }

    public function test_crear_producto_requiere_campos_obligatorios(): void
    {
        $this->asUser($this->admin)
             ->postJson('/api/v1/products', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['name', 'sale_price']);
    }

    public function test_sku_duplicado_falla(): void
    {
        Products::factory()->create([
            'company_id' => $this->company->id,
            'sku'        => 'SKU-DUP-001',
        ]);

        $this->asUser($this->admin)
             ->postJson('/api/v1/products', $this->productData(['sku' => 'SKU-DUP-001']))
             ->assertStatus(422)
             ->assertJsonValidationErrors(['sku']);
    }

    public function test_viewer_no_puede_crear_productos(): void
    {
        $this->asViewer()
             ->postJson('/api/v1/products', $this->productData())
             ->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════
    // GET /api/v1/products/{product}
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_ver_detalle_de_producto(): void
    {
        $product = Products::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->getJson("/api/v1/products/{$product->id}")
             ->assertStatus(200)
             ->assertJson(['success' => true, 'data' => ['id' => $product->id]]);
    }

    public function test_producto_inexistente_devuelve_404(): void
    {
        $this->asUser($this->admin)
             ->getJson('/api/v1/products/00000000-0000-0000-0000-000000000000')
             ->assertStatus(404);
    }

    // ══════════════════════════════════════════════════════
    // PUT /api/v1/products/{product}
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_actualizar_producto(): void
    {
        $product = Products::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->putJson("/api/v1/products/{$product->id}", ['name' => 'Nombre Actualizado'])
             ->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'Nombre Actualizado']);
    }

    public function test_viewer_no_puede_actualizar_productos(): void
    {
        $product = Products::factory()->create(['company_id' => $this->company->id]);

        $this->asViewer()
             ->putJson("/api/v1/products/{$product->id}", ['name' => 'Hack'])
             ->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════
    // DELETE /api/v1/products/{product}
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_desactivar_producto(): void
    {
        $product = Products::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->deleteJson("/api/v1/products/{$product->id}")
             ->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
    }

    // ══════════════════════════════════════════════════════
    // POST /api/v1/products/{product}/activate
    // ══════════════════════════════════════════════════════

    public function test_admin_puede_activar_producto(): void
    {
        $product = Products::factory()->inactive()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->postJson("/api/v1/products/{$product->id}/activate")
             ->assertStatus(200)
             ->assertJson(['success' => true]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
    }

    // ══════════════════════════════════════════════════════
    // Filtros de listado
    // ══════════════════════════════════════════════════════

    public function test_se_puede_filtrar_productos_por_categoria(): void
    {
        Products::factory(2)->create([
            'company_id'  => $this->company->id,
            'category_id' => $this->category->id,
        ]);
        Products::factory()->create(['company_id' => $this->company->id]);

        $response = $this->asUser($this->admin)
             ->getJson("/api/v1/products?category_id={$this->category->id}");

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(2, $response->json('meta.total'));
    }
}
