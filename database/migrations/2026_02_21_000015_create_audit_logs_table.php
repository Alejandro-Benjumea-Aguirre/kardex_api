<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 015 — audit_logs + notifications
//
// CONCEPTO: Auditoría — el registro inmutable de todo
// ═══════════════════════════════════════════════════════════
//

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {

            // ─── ID INCREMENTAL para audit logs ───────────────
            //

            $table->bigIncrements('id');

            // ─── CONTEXTO DEL TENANT ──────────────────────────
            $table->uuid('company_id')->nullable()->index();
            $table->uuid('branch_id')->nullable()->index();

            // ─── QUIÉN ────────────────────────────────────────
            $table->uuid('user_id')->nullable()->index();
            $table->string('user_email', 254)->nullable(); // Snapshot del email

            // ─── QUÉ ─────────────────────────────────────────
            //
            $table->string('event', 50);  // 'create', 'update', 'delete', 'login', etc.

            // Tabla y registro afectado (patrón polimórfico)
            $table->string('auditable_type', 100)->nullable(); // 'products', 'sales', etc.
            $table->uuid('auditable_id')->nullable();

            // ─── CAMBIOS ─────────────────────────────────────
            //
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();

            // ─── DESDE DÓNDE ──────────────────────────────────
            $table->string('ip_address', 45)->nullable();  // IPv6 puede tener hasta 45 chars
            $table->text('user_agent')->nullable();

            // URL que generó el cambio
            $table->string('url', 500)->nullable();

            // HTTP method: GET, POST, PUT, DELETE
            $table->string('http_method', 10)->nullable();

            // ─── CUÁNDO ───────────────────────────────────────
            // Solo created_at — los logs son inmutables
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));

            // ─── ÍNDICES ─────────────────────────────────────
            //
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['company_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('event');

        });

        // ─────────────────────────────────────────────────────
        // TABLA: notifications
        // Notificaciones in-app para los usuarios
        // ─────────────────────────────────────────────────────
        //
        // CONCEPTO: Notificaciones del sistema
        //
        Schema::create('notifications', function (Blueprint $table) {

            // UUID como string — convención de Laravel Notifications
            $table->uuid('id')->primary();

            // El tipo es la clase de la notificación:
            // App\Notifications\LowStockAlert
            $table->string('type');

            // Polimórfico: a quién va dirigida la notificación
            // notifiable_type = 'App\Models\User'
            // notifiable_id   = UUID del usuario
            $table->string('notifiable_type');
            $table->uuid('notifiable_id');

            // El contenido de la notificación como JSON
            // Incluye: título, mensaje, URL de acción, datos extra
            $table->jsonb('data');

            // null = no leída, timestamp = cuándo se leyó
            $table->timestampTz('read_at')->nullable();
            $table->timestampsTz();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
    }
};
