<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 004 — branch_users
//
// CONCEPTO: Tabla pivote Many-to-Many
// ═══════════════════════════════════════════════════════════
//
// PROBLEMA QUE RESUELVE:
//
// Un usuario puede trabajar en múltiples sucursales.
//   → Gerente Regional supervisa Sucursal Centro y Norte
//   → Empleado eventual cubre dos locales
//
// Una sucursal tiene múltiples usuarios.
//   → Cajero 1, Cajero 2, Supervisor, Inventario
//
// Si intentáramos resolver esto con una columna branch_id en users:
//   usuario | branch_id
//   María   | sucursal-centro   ← María en Centro
//   María   | sucursal-norte    ← ¿otro registro de María? Datos duplicados
//
// Con tabla pivote:
//   user_id | branch_id         ← una fila por relación
//   maría   | centro            ← limpio, sin duplicar datos de María
//   maría   | norte
//
// ─── CONVENCIÓN DE NOMBRES EN LARAVEL ────────────────────
//
// Laravel nombra las tablas pivote en orden alfabético:
// branch + user → branch_users (b antes que u)
//
// Si usás BelongsToMany en el Model sin especificar la tabla,
// Eloquent busca automáticamente 'branch_users'.
// Nombrarla diferente → tenés que especificarla en el Model.
// Seguir la convención = menos código.
//
// ─── DEPENDE DE ───────────────────────────────────────────
// Migración 002 (branches) y Migración 003 (users)
// deben ejecutarse ANTES que esta.
// El orden del timestamp en el nombre garantiza esto.
// ═══════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_users', function (Blueprint $table) {

            // ─── PRIMARY KEY ─────────────────────────────────
            //
            // ¿Necesita esta tabla su propio UUID?
            //
            // Alternativa A — sin PK propia, PK compuesta:
            //   $table->primary(['branch_id', 'user_id']);
            //   Más simple, pero Eloquent trabaja mejor con una PK simple.
            //
            // Alternativa B — UUID propio (lo que hacemos):
            //   Permite referenciar un registro específico de la relación
            //   desde otras tablas si fuera necesario.
            //   Ejemplo: registros de auditoría "la asignación X fue modificada".
            //
            // Para este caso ambas funcionan. UUID propio da más flexibilidad.
            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── FOREIGN KEYS ────────────────────────────────
            //
            // cascadeOnDelete() en ambas FK:
            // Si se borra una sucursal → se borran sus asignaciones de usuarios.
            // Si se borra un usuario   → se borran sus asignaciones a sucursales.
            //
            // Esto tiene sentido: si el usuario ya no existe,
            // tampoco tiene sentido que "trabaje" en alguna sucursal.
            $table->foreignUuid('branch_id')
                  ->constrained('branches')
                  ->cascadeOnDelete();

            $table->foreignUuid('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // ─── DATOS DE LA RELACIÓN ─────────────────────────
            //
            // Las tablas pivote pueden tener columnas propias.
            // No son solo IDs — pueden llevar información sobre
            // la relación en sí misma.
            //
            // is_default: ¿Es esta la sucursal predeterminada del usuario?
            // Cuando el usuario hace login, el sistema carga automáticamente
            // esta sucursal. Útil para cajeros que siempre van al mismo local.
            //
            // Solo puede haber UNA sucursal por defecto por usuario.
            // Esto se valida en la aplicación, no con un constraint de DB
            // porque PostgreSQL no soporta partial unique index fácilmente
            // en este caso. Alternativa: trigger de PostgreSQL.
            $table->boolean('is_default')->default(false);

            // assigned_at: Fecha desde cuándo trabaja en esta sucursal.
            // Útil para: "¿cuánto tiempo lleva este empleado en este local?"
            // DATE (no TIMESTAMP) porque la hora exacta no importa.
            $table->date('assigned_at')->default(DB::raw('CURRENT_DATE'));

            $table->timestampsTz();

            // ─── CONSTRAINTS ─────────────────────────────────
            //
            // UNIQUE(branch_id, user_id):
            // Un usuario solo puede estar asignado UNA VEZ por sucursal.
            // Sin esto, podrías tener:
            //   branch: centro | user: María  ← duplicado
            //   branch: centro | user: María  ← duplicado
            //
            // Este constraint lo garantiza la DB, no solo la aplicación.
            // Si el código tiene un bug e intenta insertar duplicado,
            // PostgreSQL lanza un error de violación de unique constraint.
            $table->unique(['branch_id', 'user_id']);

            // ─── ÍNDICES ─────────────────────────────────────
            //
            // Las FKs en Laravel NO crean índices automáticamente.
            // Tenés que crearlos explícitamente.
            //
            // INDEX(user_id):
            // Query: "¿En qué sucursales trabaja este usuario?"
            // → SELECT * FROM branch_users WHERE user_id = ?
            // → Se ejecuta en el login para cargar sucursales del usuario
            // → Muy frecuente, necesita índice.
            $table->index('user_id');

            // INDEX(branch_id):
            // Query: "¿Quién trabaja en esta sucursal?"
            // → SELECT * FROM branch_users WHERE branch_id = ?
            // → Se ejecuta al listar empleados de una sucursal
            // → Frecuente, necesita índice.
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        // ORDEN IMPORTA en down():
        // Primero se borra branch_users (que tiene FKs a branches y users).
        // Si intentaras borrar branches o users primero, fallaría por las FKs.
        Schema::dropIfExists('branch_users');
    }
};
