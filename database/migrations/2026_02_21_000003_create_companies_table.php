<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 001 — companies
//
// CONCEPTO: Multi-tenancy
// ═══════════════════════════════════════════════════════════
//

return new class extends Migration
{
    public function up(): void
    {

        // uuid_generate_v4() genera UUIDs aleatorios en la DB.
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');

        Schema::create('companies', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── NOMBRE DE LA EMPRESA ────────────────────────────
            //
            $table->string('name', 100);

            // ─── SLUG ────────────────────────────────────────────
            //
            // El slug es una versión URL-friendly del nombre.
            // "Mi Empresa S.A." → "mi-empresa-sa"
            $table->string('slug', 100)->unique();

            // ─── PLAN / SUSCRIPCIÓN ──────────────────────────────
            //
            $table->enum('plan', ['free', 'starter', 'professional', 'enterprise'])
                  ->default('free');

            // Límites
            //
            $table->jsonb('plan_limits')->default(json_encode([
                'max_branches'  => 1,
                'max_users'     => 3,
                'max_products'  => 100,
            ]));

            // ─── CONFIGURACIÓN GLOBAL ─────────────────────────────
            //
            $table->jsonb('settings')->default(json_encode([
                'currency'         => 'COP',  // Peso colombiano por defecto
                'timezone'         => 'America/Bogota',
                'date_format'      => 'DD/MM/YYYY',
                'tax_rate'         => 19,     // IVA Colombia
                'invoice_prefix'   => 'FAC',
            ]));

            // ─── LOGO ────────────────────────────────────────────
            //
            $table->string('logo_url')->nullable();

            // ─── ESTADO ──────────────────────────────────────────
            //
            $table->boolean('is_active')->default(true)->index();

            // ─── TIMESTAMPS ───────────────────────────────────────
            //
            $table->timestampsTz();
            $table->softDeletesTz();
        });
    }

    public function down(): void
    {
        // down() revierte exactamente lo que hizo up()
        Schema::dropIfExists('companies');
    }
};
