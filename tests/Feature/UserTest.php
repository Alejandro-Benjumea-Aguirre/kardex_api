<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Company, Role, User};
use App\Http\Middleware\JwtAuthMiddleware;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
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

        $adminRole  = Role::where('name', 'admin')->first();
        $viewerRole = Role::where('name', 'viewer')->first();

        $this->admin = User::factory()->create(['company_id' => $this->company->id]);
        $this->admin->roles()->attach($adminRole->id);

        $this->viewer = User::factory()->create(['company_id' => $this->company->id]);
        $this->viewer->roles()->attach($viewerRole->id);
    }

    // ─── HELPER ────────────────────────────────────────────

    private function asUser(User $user): static
    {
        return $this->withoutMiddleware(JwtAuthMiddleware::class)
                    ->actingAs($user);
    }

    // ═══════════════════════════════════════════════════════
    // GET /api/v1/users
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_listar_usuarios(): void
    {
        User::factory(3)->create(['company_id' => $this->company->id]);

        $response = $this->asUser($this->admin)
            ->getJson('/api/v1/users');

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure([
                     'data',
                     'meta' => ['current_page', 'per_page', 'total', 'last_page'],
                 ]);

        // admin + viewer + 3 extra = 5 mínimo
        $this->assertGreaterThanOrEqual(5, $response->json('meta.total'));
    }

    public function test_viewer_puede_listar_usuarios(): void
    {
        $this->asUser($this->viewer)
             ->getJson('/api/v1/users')
             ->assertStatus(200)
             ->assertJson(['success' => true]);
    }

    public function test_sin_autenticacion_devuelve_401(): void
    {
        $this->getJson('/api/v1/users')->assertStatus(401);
    }

    public function test_listar_con_filtro_is_active(): void
    {
        User::factory()->inactive()->create(['company_id' => $this->company->id]);

        $response = $this->asUser($this->admin)
            ->getJson('/api/v1/users?is_active=0');

        $response->assertStatus(200);
        $total = $response->json('meta.total');
        $this->assertGreaterThanOrEqual(1, $total);
    }

    // ═══════════════════════════════════════════════════════
    // POST /api/v1/users
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_crear_usuario(): void
    {
        $response = $this->asUser($this->admin)
            ->postJson('/api/v1/users', [
                'first_name'            => 'Juan',
                'last_name'             => 'Pérez',
                'email'                 => 'juan.perez@test.com',
                'password'              => 'Secret123!',
                'password_confirmation' => 'Secret123!',
            ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Usuario creado correctamente.',
                 ])
                 ->assertJsonStructure([
                     'data' => ['id', 'first_name', 'last_name', 'email', 'status'],
                 ]);

        $this->assertDatabaseHas('users', [
            'email'      => 'juan.perez@test.com',
            'company_id' => $this->company->id,
        ]);
    }

    public function test_crear_usuario_requiere_campos_obligatorios(): void
    {
        $this->asUser($this->admin)
             ->postJson('/api/v1/users', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'password']);
    }

    public function test_crear_usuario_con_email_duplicado_falla(): void
    {
        User::factory()->create([
            'company_id' => $this->company->id,
            'email'      => 'duplicado@test.com',
        ]);

        $this->asUser($this->admin)
             ->postJson('/api/v1/users', [
                 'first_name'            => 'Otro',
                 'last_name'             => 'Usuario',
                 'email'                 => 'duplicado@test.com',
                 'password'              => 'Secret123!',
                 'password_confirmation' => 'Secret123!',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
    }

    public function test_crear_usuario_con_passwords_distintas_falla(): void
    {
        $this->asUser($this->admin)
             ->postJson('/api/v1/users', [
                 'first_name'            => 'Test',
                 'last_name'             => 'User',
                 'email'                 => 'test@test.com',
                 'password'              => 'Secret123!',
                 'password_confirmation' => 'OtraPassword!',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['password']);
    }

    public function test_viewer_no_puede_crear_usuarios(): void
    {
        $this->asUser($this->viewer)
             ->postJson('/api/v1/users', [
                 'first_name'            => 'Test',
                 'last_name'             => 'User',
                 'email'                 => 'test@test.com',
                 'password'              => 'Secret123!',
                 'password_confirmation' => 'Secret123!',
             ])
             ->assertStatus(403)
             ->assertJson(['error' => ['code' => 'INSUFFICIENT_PERMISSIONS']]);
    }

    // ═══════════════════════════════════════════════════════
    // GET /api/v1/users/{user}
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_ver_detalle_de_usuario(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->getJson("/api/v1/users/{$user->id}")
             ->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'data'    => ['id' => $user->id, 'email' => $user->email],
             ]);
    }

    public function test_show_usuario_inexistente_devuelve_404(): void
    {
        $this->asUser($this->admin)
             ->getJson('/api/v1/users/00000000-0000-0000-0000-000000000000')
             ->assertStatus(404)
             ->assertJson(['error' => ['code' => 'USER_NOT_FOUND']]);
    }

    // ═══════════════════════════════════════════════════════
    // PUT /api/v1/users/{user}
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_actualizar_usuario(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->putJson("/api/v1/users/{$user->id}", [
                 'first_name' => 'NuevoNombre',
                 'last_name'  => 'NuevoApellido',
             ])
             ->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Usuario actualizado correctamente.',
             ]);

        $this->assertDatabaseHas('users', [
            'id'         => $user->id,
            'first_name' => 'NuevoNombre',
        ]);
    }

    public function test_actualizar_email_a_uno_ya_existente_falla(): void
    {
        $user1 = User::factory()->create(['company_id' => $this->company->id]);
        $user2 = User::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->putJson("/api/v1/users/{$user2->id}", ['email' => $user1->email])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['email']);
    }

    public function test_viewer_no_puede_actualizar_otros_usuarios(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->viewer)
             ->putJson("/api/v1/users/{$user->id}", ['first_name' => 'Hack'])
             ->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════
    // DELETE /api/v1/users/{user}
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_desactivar_usuario(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->deleteJson("/api/v1/users/{$user->id}")
             ->assertStatus(200)
             ->assertJson(['success' => true, 'message' => 'Usuario desactivado correctamente.']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }

    // ═══════════════════════════════════════════════════════
    // POST /api/v1/users/{user}/activate
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_reactivar_usuario(): void
    {
        $user = User::factory()->inactive()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->postJson("/api/v1/users/{$user->id}/activate")
             ->assertStatus(200)
             ->assertJson(['success' => true, 'message' => 'Usuario activado correctamente.']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => true]);
    }

    // ═══════════════════════════════════════════════════════
    // PUT /api/v1/users/{user}/password
    // ═══════════════════════════════════════════════════════

    public function test_usuario_puede_cambiar_su_propia_password(): void
    {
        $this->asUser($this->admin)
             ->putJson("/api/v1/users/{$this->admin->id}/password", [
                 'current_password'      => 'password',
                 'password'              => 'NuevaPassword123!',
                 'password_confirmation' => 'NuevaPassword123!',
             ])
             ->assertStatus(200)
             ->assertJson(['success' => true]);
    }

    public function test_cambiar_password_con_password_actual_incorrecta_falla(): void
    {
        $this->asUser($this->admin)
             ->putJson("/api/v1/users/{$this->admin->id}/password", [
                 'current_password'      => 'incorrecta',
                 'password'              => 'NuevaPassword123!',
                 'password_confirmation' => 'NuevaPassword123!',
             ])
             ->assertStatus(422)
             ->assertJson(['error' => ['code' => 'WRONG_PASSWORD']]);
    }

    // ═══════════════════════════════════════════════════════
    // POST /api/v1/users/{user}/roles
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_asignar_rol_a_usuario(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $role = Role::where('name', 'cashier')->first();

        $this->asUser($this->admin)
             ->postJson("/api/v1/users/{$user->id}/roles", [
                 'role_id' => $role->id,
             ])
             ->assertStatus(200)
             ->assertJson(['success' => true, 'message' => 'Rol asignado correctamente.']);

        $this->assertTrue($user->roles()->where('roles.id', $role->id)->exists());
    }

    public function test_asignar_rol_inexistente_devuelve_422(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);

        $this->asUser($this->admin)
             ->postJson("/api/v1/users/{$user->id}/roles", [
                 'role_id' => '00000000-0000-0000-0000-000000000000',
             ])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['role_id']);
    }

    public function test_viewer_no_puede_asignar_roles(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $role = Role::where('name', 'cashier')->first();

        $this->asUser($this->viewer)
             ->postJson("/api/v1/users/{$user->id}/roles", ['role_id' => $role->id])
             ->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════
    // DELETE /api/v1/users/{user}/roles/{role}
    // ═══════════════════════════════════════════════════════

    public function test_admin_puede_revocar_rol_de_usuario(): void
    {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $role = Role::where('name', 'cashier')->first();
        $user->roles()->attach($role->id);

        $this->asUser($this->admin)
             ->deleteJson("/api/v1/users/{$user->id}/roles/{$role->id}")
             ->assertStatus(200)
             ->assertJson(['success' => true, 'message' => 'Rol revocado correctamente.']);

        $this->assertFalse($user->roles()->where('roles.id', $role->id)->exists());
    }
}
