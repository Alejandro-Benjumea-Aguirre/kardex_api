<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 009 — user_roles
//

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

            $table->foreignUuid('branch_id')
                  ->nullable()
                  ->constrained('branches')
                  ->nullOnDelete();

            // ─── VIGENCIA DEL ROL ─────────────────────────────
            //

            $table->timestampTz('expires_at')->nullable();

            // ─── AUDITORÍA DE LA ASIGNACIÓN ──────────────────
            //

            $table->foreignUuid('assigned_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestampsTz();

            // ─── CONSTRAINT DE UNICIDAD ───────────────────────
            //

            $table->unique(['user_id', 'role_id', 'branch_id']);

            // ─── ÍNDICES ─────────────────────────────────────
            //

            $table->index('user_id');
            $table->index('role_id');
            $table->index(['user_id', 'branch_id']);

        });

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
