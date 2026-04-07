<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 007 — user_roles
//
// CONCEPTO: Tabla pivote users ↔ roles
// DECISIÓN DE DISEÑO CRÍTICA: Roles globales vs por sucursal
// ═══════════════════════════════════════════════════════════
//
// Esta tabla responde: "¿Qué roles tiene el usuario X?"
//
//   users ──M:M──► roles
//          via
//       user_roles
//
// Pero hay una complejidad importante para un POS multi-sucursal:
//
// ─── PROBLEMA: ¿El rol es por empresa o por sucursal? ────
//
// OPCIÓN A — Rol por empresa:
//   María es "Admin" de toda la empresa.
//   Puede hacer todo en todas las sucursales.
//
//   user_roles: { user_id: María, role_id: Admin }
//
// OPCIÓN B — Rol por sucursal:
//   María es "Admin" en Sucursal Centro
//   pero solo "Viewer" en Sucursal Norte.
//
//   user_roles: { user_id: María, role_id: Admin,  branch_id: Centro }
//   user_roles: { user_id: María, role_id: Viewer, branch_id: Norte  }
//
// ─── ¿CUÁL ELEGIR? ────────────────────────────────────────
//
// Para SalesPoint elegimos AMBAS con branch_id nullable:
//
//   branch_id = NULL  → el rol aplica a TODA la empresa
//   branch_id = UUID  → el rol aplica SOLO a esa sucursal
//
// Esto da máxima flexibilidad:
//
//   María: Admin sin branch_id   → Admin de toda la empresa
//   Pedro: Cashier branch=Centro → Cajero solo en Centro
//   Juan:  Manager branch=Norte  → Gerente solo en Norte
//        + Viewer branch=Centro  → Solo lectura en Centro
//
// ─── LÓGICA DE EVALUACIÓN ─────────────────────────────────
//
// Cuando se verifica si un usuario puede hacer algo en una sucursal:
//
//   1. Buscar roles SIN branch_id (aplican a toda la empresa)
//   2. Buscar roles CON branch_id = sucursal_actual
//   3. Combinar todos los permisos de ambos sets de roles
//   4. Verificar si el permiso requerido está en el set combinado
//
// Esto se cachea en Redis después del login para no hacer
// estos JOINs en cada request.
// ═══════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_roles', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── USUARIO Y ROL ────────────────────────────────
            $table->foreignUuid('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->foreignUuid('role_id')
                  ->constrained('roles')
                  ->cascadeOnDelete();

            // ─── SCOPE DE LA SUCURSAL ─────────────────────────
            //
            // NULL  → el rol aplica a toda la empresa del usuario
            // UUID  → el rol aplica solo a esta sucursal específica
            //
            // nullOnDelete: si se borra la sucursal, el rol queda
            // sin scope de sucursal (pasa a ser de empresa entera).
            // Alternativa: cascadeOnDelete → borrar la asignación.
            // Elegimos nullOnDelete para no dejar al usuario sin acceso
            // accidentalmente si se borra una sucursal.
            $table->foreignUuid('branch_id')
                  ->nullable()
                  ->constrained('branches')
                  ->nullOnDelete();

            // ─── VIGENCIA DEL ROL ─────────────────────────────
            //
            // ¿Desde cuándo y hasta cuándo tiene este rol?
            //
            // expires_at es especialmente útil para:
            //   - Empleados temporales: "cajero hasta el 31 de enero"
            //   - Accesos de auditoría: "el auditor puede ver reportes
            //     solo durante la semana de auditoría"
            //   - Prueba de un rol: "acceso de manager por 30 días"
            //
            // El middleware de autorización verifica expires_at en
            // cada request y deniega si el rol expiró.
            $table->timestampTz('expires_at')->nullable();

            // ─── AUDITORÍA DE LA ASIGNACIÓN ──────────────────
            //
            // ¿Quién asignó este rol a este usuario?
            // Fundamental para saber "¿quién le dio acceso a María?"
            $table->foreignUuid('assigned_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestampsTz();

            // ─── CONSTRAINT DE UNICIDAD ───────────────────────
            //
            // Un usuario no puede tener el mismo rol dos veces
            // en el mismo scope (empresa o sucursal).
            //
            // TRICKY: el UNIQUE con nullable no funciona exactamente
            // igual en todos los motores. En PostgreSQL, dos NULLs
            // NO son iguales, entonces:
            //   user: María | role: Admin | branch: NULL
            //   user: María | role: Admin | branch: NULL
            //   ← PostgreSQL permite esto porque NULL != NULL
            //
            // Solución: usar un partial unique index de PostgreSQL
            // que trate los NULLs correctamente.
            $table->unique(['user_id', 'role_id', 'branch_id']);

            // ─── ÍNDICES ─────────────────────────────────────
            //
            // INDEX(user_id): "¿Qué roles tiene este usuario?"
            // Ejecutado en cada login y verificación de autorización.
            $table->index('user_id');

            // INDEX(role_id): "¿Qué usuarios tienen el rol X?"
            // Útil para: "muéstrame todos los admins de la empresa"
            $table->index('role_id');

            // INDEX(user_id + branch_id): Para cargar roles del
            // usuario en una sucursal específica eficientemente
            $table->index(['user_id', 'branch_id']);

        });

        // Para el caso de branch = NULL, necesitamos otro índice:
        DB::statement('
            CREATE UNIQUE INDEX user_roles_company_scope_unique
            ON user_roles (user_id, role_id)
            WHERE branch_id IS NULL
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
    }
};
