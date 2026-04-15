<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 004 — branch_users
//


return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_users', function (Blueprint $table) {

            // ─── PRIMARY KEY ─────────────────────────────────
            //

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── FOREIGN KEYS ────────────────────────────────
            //

            $table->foreignUuid('branch_id')
                  ->constrained('branches')
                  ->cascadeOnDelete();

            $table->foreignUuid('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // ─── DATOS DE LA RELACIÓN ─────────────────────────
            //

            $table->boolean('is_default')->default(false);
            $table->date('assigned_at')->default(DB::raw('CURRENT_DATE'));
            $table->timestampsTz();

            // ─── CONSTRAINTS ─────────────────────────────────
            //

            $table->unique(['branch_id', 'user_id']);

            // ─── ÍNDICES ─────────────────────────────────────
            //

            $table->index('user_id');
            $table->index('branch_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_users');
    }
};
