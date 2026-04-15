<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 004 — roles
//
// CONCEPTO: ¿Qué es un Rol?
// ═══════════════════════════════════════════════════════════
//
// Un rol es un nombre que agrupa permisos.
// En vez de asignarle 20 permisos individuales a cada cajero,
// creás el rol "Cajero" con esos 20 permisos, y luego
// asignás ese rol a todos los cajeros.
//
// Cambiar lo que puede hacer un cajero = editar el rol.
// Todos los cajeros heredan el cambio automáticamente.
//
// ─── ROLES POR EMPRESA vs ROLES GLOBALES ─────────────────
//
// Hay dos modelos:
//
// ROLES GLOBALES (company_id = NULL):
//   Existen a nivel del sistema entero.
//   Todas las empresas comparten los mismos roles.
//   ✅ Más simple — un solo set de roles para mantener
//   ❌ Rígido — no podés personalizar roles por empresa
//   → Usalo cuando: todas las empresas tienen la misma
//     estructura operativa (cadenas de tiendas idénticas)
//
// ROLES POR EMPRESA (company_id = UUID):
//   Cada empresa define sus propios roles.
//   ✅ Flexible — "Empresa A" puede tener un rol "Supervisor
//     de turno" que "Empresa B" no tiene
//   ✅ Cada empresa controla su propia seguridad
//   ❌ Más complejo — hay que seed roles para cada empresa nueva
//   → Usalo cuando: las empresas tienen estructuras diferentes
//
// MODELO HÍBRIDO (lo que hacemos):
//   Roles con company_id = NULL son "roles del sistema"
//   que aplican a todos los tenants (super_admin, etc.)
//   Roles con company_id = UUID son roles propios de esa empresa.
//   ✅ Lo mejor de ambos mundos
// ═══════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── SCOPE DEL ROL ────────────────────────────────
            //

            $table->foreignUuid('company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->cascadeOnDelete();

            // ─── IDENTIFICACIÓN DEL ROL ───────────────────────
            //

            $table->string('name', 100);
            $table->string('display_name', 150);
            $table->text('description')->nullable();

            // ─── ROL PREDETERMINADO ────────────────────────────
            //

            $table->boolean('is_default')->default(false);

            // ─── ROL DEL SISTEMA ──────────────────────────────
            //

            $table->boolean('is_system')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestampsTz();
            $table->softDeletes();

            // ─── ÍNDICES ─────────────────────────────────────
            //

            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
