<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Company, Permission, Role, User};
use App\Http\Middleware\JwtAuthMiddleware;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $viewer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $company = Company::factory()->create();

        $adminRole  = Role::where('name', 'admin')->first();
        $viewerRole = Role::where('name', 'viewer')->first();

        $this->admin = User::factory()->create(['company_id' => $company->id]);
        $this->admin->roles()->attach($adminRole->id);

        $this->viewer = User::factory()->create(['company_id' => $company->id]);
        $this->viewer->roles()->attach($viewerRole->id);
    }

    // ─── HELPER ────────────────────────────────────────────

    private function asUser(User $user): static
    {
        return $this->withoutMiddleware(JwtAuthMiddleware::class)
                    ->actingAs($user);
    }

    // ═══════════════════════════════════════════════════════
    // GET /api/v1/roles
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_listar_roles(): void
    {
        $response = $this->asUser($this->admin)
            ->getJson('/api/v1/roles');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'data' => [['id', 'name', 'display_name', 'flags', 'permissions']],
                 ]);
    }

    public function test_viewer_no_puede_listar_roles(): void
    {
        // El rol viewer no tiene permiso 'roles:read'
        $this->asUser($this->viewer)
             ->getJson('/api/v1/roles')
             ->assertStatus(403);
    }

    public function test_sin_autenticacion_devuelve_401(): void
    {
        $this->getJson('/api/v1/roles')
             ->assertStatus(401);
    }

    public function test_la_lista_incluye_roles_del_sistema(): void
    {
        $response = $this->asUser($this->admin)
            ->getJson('/api/v1/roles');

        $names = collect($response->json('data'))->pluck('name')->toArray();

        $this->assertContains('admin', $names);
        $this->assertContains('cashier', $names);
    }

    // ═══════════════════════════════════════════════════════
    // GET /api/v1/roles/{role}
    // ═══════════════════════════════════════════════════════

    public function test_show_devuelve_rol_con_permisos(): void
    {
        $role = Role::where('name', 'admin')->first();

        $response = $this->asUser($this->admin)
            ->getJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => ['id' => $role->id, 'name' => 'admin'],
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'id', 'name', 'display_name', 'flags',
                         'permissions', 'created_at', 'updated_at',
                     ],
                 ]);

        // El rol admin tiene permisos cargados y agrupados por módulo
        $permissions = $response->json('data.permissions');
        $this->assertNotEmpty($permissions);
    }

    public function test_show_rol_inexistente_devuelve_404(): void
    {
        $this->asUser($this->admin)
             ->getJson('/api/v1/roles/00000000-0000-0000-0000-000000000000')
             ->assertStatus(404)
             ->assertJson(['success' => false]);
    }

    // ═══════════════════════════════════════════════════════
    // POST /api/v1/roles
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_crear_rol(): void
    {
        $response = $this->asUser($this->admin)
            ->postJson('/api/v1/roles', [
                'display_name' => 'Rol de Prueba',
                'description'  => 'Creado en test',
            ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Rol creado correctamente.',
                 ])
                 ->assertJsonStructure([
                     'data' => ['id', 'name', 'display_name'],
                 ]);

        $this->assertDatabaseHas('roles', [
            'display_name' => 'Rol de Prueba',
            'company_id'   => $this->admin->company_id,
        ]);
    }

    public function test_crear_rol_requiere_display_name(): void
    {
        $this->asUser($this->admin)
             ->postJson('/api/v1/roles', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['display_name']);
    }

    public function test_viewer_no_puede_crear_roles(): void
    {
        $this->asUser($this->viewer)
             ->postJson('/api/v1/roles', ['display_name' => 'Sin permiso'])
             ->assertStatus(403)
             ->assertJson(['error' => ['code' => 'INSUFFICIENT_PERMISSIONS']]);
    }

    // ═══════════════════════════════════════════════════════
    // PUT /api/v1/roles/{role}
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_actualizar_rol_personalizado(): void
    {
        $role = Role::create([
            'company_id'   => $this->admin->company_id,
            'name'         => 'rol-custom',
            'display_name' => 'Rol Custom',
            'is_system'    => false,
            'is_active'    => true,
        ]);

        $response = $this->asUser($this->admin)
            ->putJson("/api/v1/roles/{$role->id}", [
                'display_name' => 'Rol Custom Actualizado',
            ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Rol actualizado correctamente.',
                 ]);

        $this->assertDatabaseHas('roles', [
            'id'           => $role->id,
            'display_name' => 'Rol Custom Actualizado',
        ]);
    }

    public function test_no_se_puede_actualizar_rol_del_sistema(): void
    {
        $systemRole = Role::where('name', 'admin')->first();

        $this->asUser($this->admin)
             ->putJson("/api/v1/roles/{$systemRole->id}", [
                 'display_name' => 'Cambio no permitido',
             ])
             ->assertStatus(403);
    }

    public function test_viewer_no_puede_actualizar_roles(): void
    {
        $role = Role::create([
            'company_id'   => $this->admin->company_id,
            'name'         => 'rol-viewer-test',
            'display_name' => 'Test Viewer',
            'is_system'    => false,
            'is_active'    => true,
        ]);

        $this->asUser($this->viewer)
             ->putJson("/api/v1/roles/{$role->id}", ['display_name' => 'Hack'])
             ->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════
    // DELETE /api/v1/roles/{role}
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_eliminar_rol_personalizado_sin_usuarios(): void
    {
        $role = Role::create([
            'company_id'   => $this->admin->company_id,
            'name'         => 'rol-eliminable',
            'display_name' => 'Rol Eliminable',
            'is_system'    => false,
            'is_active'    => true,
        ]);

        $this->asUser($this->admin)
             ->deleteJson("/api/v1/roles/{$role->id}")
             ->assertStatus(200)
             ->assertJson(['success' => true, 'message' => 'Rol eliminado correctamente.']);

        $this->assertSoftDeleted('roles', ['id' => $role->id]);
    }

    public function test_viewer_no_puede_eliminar_roles(): void
    {
        $role = Role::create([
            'company_id'   => $this->admin->company_id,
            'name'         => 'rol-no-delete',
            'display_name' => 'Sin Borrar',
            'is_system'    => false,
            'is_active'    => true,
        ]);

        $this->asUser($this->viewer)
             ->deleteJson("/api/v1/roles/{$role->id}")
             ->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════
    // PUT /api/v1/roles/{role}/permissions
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_sincronizar_permisos_de_rol(): void
    {
        $role = Role::create([
            'company_id'   => $this->admin->company_id,
            'name'         => 'rol-sync',
            'display_name' => 'Rol Sync',
            'is_system'    => false,
            'is_active'    => true,
        ]);

        $permIds = Permission::where('name', 'LIKE', 'products:%')
            ->limit(2)
            ->pluck('id')
            ->toArray();

        $this->asUser($this->admin)
             ->putJson("/api/v1/roles/{$role->id}/permissions", [
                 'permission_ids' => $permIds,
             ])
             ->assertStatus(200)
             ->assertJson(['success' => true, 'message' => 'Permisos del rol actualizados.']);

        $this->assertCount(2, $role->fresh()->permissions);
    }

    public function test_sincronizar_con_array_vacio_quita_todos_los_permisos(): void
    {
        $role = Role::create([
            'company_id'   => $this->admin->company_id,
            'name'         => 'rol-vaciar',
            'display_name' => 'Vaciar',
            'is_system'    => false,
            'is_active'    => true,
        ]);
        $role->permissions()->attach(Permission::first()->id);

        $this->asUser($this->admin)
             ->putJson("/api/v1/roles/{$role->id}/permissions", [
                 'permission_ids' => [],
             ])
             ->assertStatus(200);

        $this->assertCount(0, $role->fresh()->permissions);
    }

    public function test_sincronizar_sin_permission_ids_devuelve_422(): void
    {
        $role = Role::create([
            'company_id'   => $this->admin->company_id,
            'name'         => 'rol-val',
            'display_name' => 'Validación',
            'is_system'    => false,
            'is_active'    => true,
        ]);

        $this->asUser($this->admin)
             ->putJson("/api/v1/roles/{$role->id}/permissions", [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['permission_ids']);
    }
}
