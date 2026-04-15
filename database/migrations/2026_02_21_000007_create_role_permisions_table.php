<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 007 — role_permissions
//


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── FOREIGN KEYS ────────────────────────────────
            //

            $table->foreignUuid('role_id')
                  ->constrained('roles')
                  ->cascadeOnDelete();

            $table->foreignUuid('permission_id')
                  ->constrained('permissions')
                  ->cascadeOnDelete();

            // ─── QUIÉN ASIGNÓ ESTE PERMISO ────────────────────
            //

            $table->foreignUuid('granted_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestampsTz();

            // ─── CONSTRAINT DE UNICIDAD ───────────────────────
            //

            $table->unique(['role_id', 'permission_id']);

            // ─── ÍNDICES ─────────────────────────────────────
            //

            $table->index('role_id');

            $table->index('permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
