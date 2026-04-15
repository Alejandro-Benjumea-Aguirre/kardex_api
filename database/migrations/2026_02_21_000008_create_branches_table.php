<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 008 — branches (sucursales)
//

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── FOREIGN KEY: company_id ──────────────────────
            //

            $table->foreignUuid('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();

            // ─── DATOS BÁSICOS ────────────────────────────────
            $table->string('name', 100);

            $table->string('code', 20);

            // ─── DIRECCIÓN ───────────────────────────────────
            // 
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 2)->default('CO'); // ISO 3166-1 alpha-2

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();  // 11 porque longitude va -180 a 180

            // ─── CONTACTO ────────────────────────────────────
            $table->string('phone', 20)->nullable();
            $table->string('email', 254)->nullable();

            // ─── CONFIGURACIÓN POR SUCURSAL ───────────────────
            //

            $table->jsonb('settings')->default(json_encode([
                'opening_time'     => '08:00',
                'closing_time'     => '20:00',
                'receipt_printer'  => null,
                'allow_credit'     => false,  // ¿Permite ventas a crédito?
            ]));

            // ─── NUMERACIÓN DE DOCUMENTOS ─────────────────────
            //

            $table->unsignedBigInteger('invoice_counter')->default(0);

            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_main')->default(false); // Sucursal principal de la empresa

            $table->timestampsTz();
            $table->softDeletes(); // deleted_at — nunca borrar sucursales con historial

            // ─── ÍNDICES ─────────────────────────────────────
            //

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']); // Para listar sucursales activas
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
