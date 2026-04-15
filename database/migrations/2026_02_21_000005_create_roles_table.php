<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 005 — roles
//
// CONCEPTO: ¿Qué es un Rol?
// ═══════════════════════════════════════════════════════════
//

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
