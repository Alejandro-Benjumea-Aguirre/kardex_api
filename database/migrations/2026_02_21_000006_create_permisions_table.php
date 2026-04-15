<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 005 — permissions
//

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── SCOPE DEL PERMISO ────────────────────────────
            // 
            $table->foreignUuid('company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->cascadeOnDelete();

            // ─── IDENTIFICACIÓN ───────────────────────────────
            //

            $table->string('name', 150);
            $table->string('display_name', 200);
            $table->text('description')->nullable();

            // ─── ORGANIZACIÓN POR MÓDULO ──────────────────────
            //

            $table->string('module', 100);

            // ─── ORDEN DE VISUALIZACIÓN ───────────────────────
            // 

            $table->unsignedSmallInteger('sort_order')->default(0);

            // ─── PERMISO DEL SISTEMA ──────────────────────────
            // 
            
            $table->boolean('is_system')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestampsTz();

            // name único dentro del scope
            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
