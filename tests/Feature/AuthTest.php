<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\{Company, Role, User};
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $company    = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $company->id]);
    }

    // ══════════════════════════════════════════════════════
    // POST /api/v1/auth/login
    // ══════════════════════════════════════════════════════

    public function test_login_con_credenciales_validas(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertStatus(200)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure(['data' => ['access_token', 'user']]);

        // refresh_token va como cookie httpOnly
        $this->assertNotNull($response->headers->getCookies());
    }

    public function test_login_con_password_incorrecta_devuelve_401(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email'    => $this->user->email,
            'password' => 'wrongpassword',
        ])->assertStatus(401);
    }

    public function test_login_con_email_inexistente_devuelve_401(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email'    => 'noexiste@test.com',
            'password' => 'password',
        ])->assertStatus(401);
    }

    public function test_login_campos_obligatorios(): void
    {
        $this->postJson('/api/v1/auth/login', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_usuario_inactivo_devuelve_403(): void
    {
        $inactive = User::factory()->inactive()->create([
            'company_id' => $this->user->company_id,
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email'    => $inactive->email,
            'password' => 'password',
        ])->assertStatus(403);
    }

    // ══════════════════════════════════════════════════════
    // POST /api/v1/auth/register
    // ══════════════════════════════════════════════════════

    private function registerPayload(array $overrides = []): array
    {
        return array_merge([
            'company' => [
                'name'    => 'Mi Empresa Test',
                'nit'     => '900123456-1',
                'sector'  => 'Tecnología',
                'phone'   => '3001234567',
                'address' => 'Calle 123 #45-67',
            ],
            'user' => [
                'first_name'            => 'Ana',
                'last_name'             => 'García',
                'email'                 => 'ana.garcia@test.com',
                'password'              => 'Secret123!',
                'password_confirmation' => 'Secret123!',
            ],
        ], $overrides);
    }

    public function test_registro_crea_usuario_y_empresa(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->registerPayload());

        $response->assertStatus(201)
                 ->assertJson(['success' => true])
                 ->assertJsonStructure(['data' => ['id', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'ana.garcia@test.com']);
        $this->assertDatabaseHas('companies', ['name' => 'Mi Empresa Test']);
    }

    public function test_registro_requiere_campos_obligatorios(): void
    {
        $this->postJson('/api/v1/auth/register', [])
             ->assertStatus(422)
             ->assertJsonValidationErrors([
                 'company.name', 'company.nit', 'company.sector',
                 'company.phone', 'company.address',
                 'user.first_name', 'user.last_name', 'user.email', 'user.password',
             ]);
    }

    public function test_registro_con_email_duplicado_falla(): void
    {
        $payload = $this->registerPayload();
        $payload['user']['email'] = $this->user->email;

        $this->postJson('/api/v1/auth/register', $payload)
             ->assertStatus(422)
             ->assertJsonValidationErrors(['user.email']);
    }

    // ══════════════════════════════════════════════════════
    // GET /api/v1/auth/me
    // ══════════════════════════════════════════════════════

    public function test_me_sin_token_devuelve_401(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }
}
