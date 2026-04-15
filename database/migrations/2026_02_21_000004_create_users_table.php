<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 004 — users
//
// CONCEPTO: Usuarios en contexto multi-tenant
// ═══════════════════════════════════════════════════════════
//

// ROLES en SalesPoint:
//   super_admin → dueño del sistema (ve todas las empresas)
//   admin       → dueño de la empresa (ve todas sus sucursales)
//   manager     → gerente (ve sus sucursales asignadas)
//   cashier     → cajero (solo su sucursal, solo ventas)
//   inventory   → encargado de inventario
//   viewer      → solo lectura (reportes)
// ═══════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── MULTI-TENANT: company_id ─────────────────────
            //
            
            $table->foreignUuid('company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->nullOnDelete();

            // ─── DATOS PERSONALES ─────────────────────────────
            $table->string('first_name', 50);
            $table->string('last_name', 50);

            // ─── EMAIL ────────────────────────────────────────
            //
            // Email único GLOBALMENTE (no por empresa).
            //

            $table->string('email', 254)->unique();

            // ─── PASSWORD ────────────────────────────────────
            //

            $table->string('password');

            // ─── PERFIL ───────────────────────────────────────
            $table->string('avatar_url')->nullable();
            $table->string('phone', 20)->nullable();

            // ─── ESTADO DE LA CUENTA ──────────────────────────
            $table->boolean('is_active')->default(true);
            $table->boolean('is_email_verified')->default(false);

            // ─── SEGURIDAD ────────────────────────────────────
            //

            $table->timestampTz('last_login_at')->nullable();

            // ─── TIMESTAMPS ──────────────────────────────────
            //

            $table->timestampsTz();

            $table->softDeletes();

            // ─── ÍNDICES ─────────────────────────────────────
            //
            
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
