<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 006 — role_permissions
//
// CONCEPTO: Tabla pivote roles ↔ permissions
// ═══════════════════════════════════════════════════════════
//
// Esta tabla responde: "¿Qué permisos tiene el rol X?"
//
//   roles ──M:M──► permissions
//          via
//      role_permissions
//
// Ejemplo de datos:
//   role_id: admin-uuid   | permission_id: product:create-uuid
//   role_id: admin-uuid   | permission_id: product:delete-uuid
//   role_id: cashier-uuid | permission_id: sale:create-uuid
//   role_id: cashier-uuid | permission_id: product:read-uuid
//
// Un admin puede todo con productos.
// Un cajero solo puede crear ventas y ver productos.
//
// ─── ¿ESTA TABLA NECESITA SU PROPIO ID? ──────────────────
//
// Muchos tutoriales usan PK compuesta: PRIMARY KEY(role_id, permission_id)
// Es técnicamente correcto pero tiene una limitación:
// si en el futuro necesitás referenciar una asignación específica
// desde otra tabla, no podés porque no hay un ID simple.
//
// Usamos UUID propio por consistencia y flexibilidad.
// El UNIQUE(role_id, permission_id) garantiza la unicidad
// igual que lo haría la PK compuesta.
//
// ─── ¿POR QUÉ NO JSONB EN roles? ─────────────────────────
//
// Alternativa: guardar permisos como array JSONB en roles:
//   roles: { id, name, permissions: ['product:create', 'sale:read'] }
//
// Ventajas del JSONB:
//   ✅ Una sola tabla menos
//   ✅ Query más simple para leer permisos de un rol
//
// Desventajas del JSONB:
//   ❌ No podés hacer JOIN: "todos los roles que tienen product:create"
//   ❌ No podés hacer FK: los permisos en el JSON no están validados
//   ❌ Difícil auditar cambios (¿quién agregó qué permiso cuándo?)
//   ❌ Para revocar un permiso hay que modificar el array completo
//
// Con tabla separada todo eso es trivial.
// La regla general: si el dato tiene relaciones, va en tabla. No en JSON.
// ═══════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── FOREIGN KEYS ────────────────────────────────
            //
            // cascadeOnDelete en ambas:
            // Si se borra el rol → se borran sus asignaciones de permisos.
            // Si se borra el permiso → se borra de todos los roles que lo tienen.
            //
            // Esto es lo correcto: si el permiso 'product:create' deja
            // de existir, ningún rol debería seguir "teniéndolo".
            $table->foreignUuid('role_id')
                  ->constrained('roles')
                  ->cascadeOnDelete();

            $table->foreignUuid('permission_id')
                  ->constrained('permissions')
                  ->cascadeOnDelete();

            // ─── QUIÉN ASIGNÓ ESTE PERMISO ────────────────────
            //
            // Auditoría de la asignación.
            // "¿Quién le dio el permiso product:delete al rol Cajero?"
            // Este campo responde esa pregunta.
            //
            // nullable porque los permisos del seed inicial
            // no tienen un usuario que los creó.
            $table->foreignUuid('granted_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestampsTz();

            // ─── CONSTRAINT DE UNICIDAD ───────────────────────
            //
            // Un permiso solo puede estar asignado UNA VEZ a un rol.
            // Sin esto podrías tener:
            //   role: admin | permission: product:create  ← duplicado
            //   role: admin | permission: product:create  ← duplicado
            //
            // El UNIQUE lo previene a nivel de DB.
            $table->unique(['role_id', 'permission_id']);

            // ─── ÍNDICES ─────────────────────────────────────
            //
            // INDEX(role_id): "¿Qué permisos tiene este rol?"
            // Esta es la query más frecuente — se ejecuta en cada
            // verificación de autorización.
            $table->index('role_id');

            // INDEX(permission_id): "¿Qué roles tienen este permiso?"
            // Menos frecuente pero útil para: "quiero ver quién puede
            // borrar productos antes de eliminar ese permiso"
            $table->index('permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
