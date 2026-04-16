<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 003 — companies
//
// CONCEPTO: Multi-tenancy
// ═══════════════════════════════════════════════════════════
//

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');

        Schema::create('companies', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── NOMBRE DE LA EMPRESA ────────────────────────────
            $table->string('name', 100);

            // ─── NIT ─────────────────────────────────────────────
            $table->string('nit', 20)->unique();

            // ─── SECTOR COMERCIAL ────────────────────────────────
            $table->unsignedInteger('sector');

            // ─── TELÉFONO ────────────────────────────────────────
            $table->string('phone', 15);

            // ─── DIRECCIÓN ───────────────────────────────────────
            $table->string('address', 50);

            // ─── CIUDAD ──────────────────────────────────────────
            $table->unsignedInteger('city');

            // ─── PAÍS ────────────────────────────────────────────
            $table->unsignedInteger('country');
                                                       
            // ─── SITIO WEB ───────────────────────────────────────
            $table->string('website', 100)->nullable();

            // ─── SLUG ────────────────────────────────────────────
            $table->string('slug', 100)->unique();

            // ─── PLAN / SUSCRIPCIÓN ──────────────────────────────
            $table->enum('plan', ['free', 'starter', 'professional', 'enterprise'])
                ->default('free');

            // ─── LÍMITES DEL PLAN ────────────────────────────────
            $table->jsonb('plan_limits')->default(json_encode([
                'max_branches' => 1,
                'max_users'    => 3,
                'max_products' => 100,
            ]));

            // ─── CONFIGURACIÓN GLOBAL ────────────────────────────
            $table->jsonb('settings')->default(json_encode([
                'currency'       => 'COP',
                'timezone'       => 'America/Bogota',
                'date_format'    => 'DD/MM/YYYY',
                'tax_rate'       => 19,
                'invoice_prefix' => 'FAC',
            ]));

            // ─── LOGO ────────────────────────────────────────────
            $table->string('logo_url')->nullable();

            // ─── ESTADO ──────────────────────────────────────────
            $table->boolean('is_active')->default(true)->index();

            // ─── TIMESTAMPS ──────────────────────────────────────
            $table->timestampsTz();
            $table->softDeletesTz();
        });

        // ─── TABLA PAISES ──────────────────────────────────────
        //
        Schema::create('countries', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->string('name', 100);                // "Colombia"
            $table->string('native_name', 100)->nullable(); // "Colombia" en idioma nativo
            $table->string('iso2', 2)->unique();         // "CO"
            $table->string('iso3', 3)->unique();         // "COL"
            $table->string('phone_code', 10);            // "+57"
            $table->string('capital', 100)->nullable();  // "Bogotá"
            $table->string('currency', 3)->nullable();   // "COP"
            $table->string('currency_symbol', 10)->nullable(); // "$"
            $table->string('region', 50)->nullable();    // "Americas"
            $table->string('subregion', 100)->nullable();// "South America"
            $table->string('flag', 10)->nullable();      // "🇨🇴"
            $table->boolean('is_active')->default(true);

            $table->timestampsTz();
        });
    
        // ─── TABLA Ciudades ──────────────────────────────────────
        //
        Schema::create('cities', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── RELACIÓN CON PAÍS ────────────────────────────
            $table->foreignUuid('country_id')
                  ->constrained('countries')
                  ->cascadeOnDelete();

            // ─── DATOS BÁSICOS ────────────────────────────────
            $table->string('name', 100);

            // ─── CÓDIGO DANE ──────────────────────────────────
            // Código oficial del DANE para municipios colombianos
            // Ej: Bogotá = 11001, Medellín = 05001
            $table->string('dane_code', 10)->nullable()->unique();

            // ─── DEPARTAMENTO ─────────────────────────────────
            $table->string('department', 100)->nullable();        // "Cundinamarca"
            $table->string('department_code', 5)->nullable();     // "11"

            // ─── DATOS GEOGRÁFICOS ────────────────────────────
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // ─── ESTADO ──────────────────────────────────────
            $table->boolean('is_active')->default(true)->index();

            $table->timestampsTz();

            // ─── ÍNDICES ─────────────────────────────────────
            $table->index(['country_id', 'is_active']);
            $table->index('department_code');
            $table->index('dane_code');
        });

    }

    public function down(): void
    {
        // down() revierte exactamente lo que hizo up()
        Schema::dropIfExists('companies');
    }
};
