<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 005 — inventory + stock_movements
//
// CONCEPTO: El corazón del sistema de inventarios
// ═══════════════════════════════════════════════════════════
//

return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────
        // TABLA: inventory
        // Stock actual por sucursal y variante de producto
        // ─────────────────────────────────────────────────────
        Schema::create('inventory', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('branch_id')
                  ->constrained('branches')
                  ->cascadeOnDelete();

            $table->foreignUuid('product_variant_id')
                  ->constrained('product_variants')
                  ->cascadeOnDelete();

            // ─── STOCK ───────────────────────────────────────
            //
            $table->decimal('quantity', 10, 3)->default(0);

            $table->decimal('min_stock', 10, 3)->default(0);

            $table->decimal('max_stock', 10, 3)->nullable();

            // ─── UBICACIÓN FÍSICA ─────────────────────────────
            // 
            $table->string('location', 50)->nullable(); // "A3-2"

            // ─── COSTO PROMEDIO PONDERADO ─────────────────────
            //
            // 
            $table->decimal('avg_cost', 12, 4)->default(0); // 4 decimales para precisión

            $table->timestampsTz();

            $table->unique(['branch_id', 'product_variant_id']);

            $table->index(['branch_id', 'quantity']);
        });

        // ─────────────────────────────────────────────────────
        // TABLA: stock_movements
        // Historial inmutable de todos los movimientos de stock
        // ─────────────────────────────────────────────────────
        //
        Schema::create('stock_movements', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('branch_id')
                  ->constrained('branches')
                  ->cascadeOnDelete();

            $table->foreignUuid('product_variant_id')
                  ->constrained('product_variants')
                  ->cascadeOnDelete();

            // ─── TIPO DE MOVIMIENTO ───────────────────────────
            //

            $table->enum('type', [
                'purchase',       // Entrada por compra a proveedor
                'sale',           // Salida por venta
                'sale_return',    // Entrada por devolución de cliente
                'purchase_return',// Salida por devolución a proveedor
                'adjustment_in',  // Ajuste manual positivo
                'adjustment_out', // Ajuste manual negativo
                'transfer_in',    // Transferencia recibida de otra sucursal
                'transfer_out',   // Transferencia enviada a otra sucursal
                'waste',          // Merma, daño, vencimiento
                'initial',        // Stock inicial al crear el inventario
            ]);

            // ─── CANTIDADES ───────────────────────────────────
            //
            $table->decimal('quantity', 10, 3);         // Siempre positivo
            $table->decimal('stock_before', 10, 3);     // Stock antes del movimiento
            $table->decimal('stock_after', 10, 3);      // Stock después del movimiento

            // ─── COSTO ────────────────────────────────────────
            $table->decimal('unit_cost', 12, 4)->nullable(); // Costo unitario en este movimiento

            // ─── REFERENCIA AL DOCUMENTO ORIGEN ──────────────
            //
            $table->string('reference_type', 50)->nullable(); // 'sale', 'purchase', etc.
            $table->uuid('reference_id')->nullable();          // ID del documento

            // Notas del operador que realizó el movimiento
            $table->text('notes')->nullable();

            // ─── QUIÉN HIZO EL MOVIMIENTO ─────────────────────
            $table->foreignUuid('created_by')
                  ->constrained('users')
                  ->restrictOnDelete(); // No borrar usuario si tiene movimientos

            $table->timestampTz('created_at')->default(DB::raw('NOW()'));

            // ─── ÍNDICES ─────────────────────────────────────
            //
            $table->index(['branch_id', 'product_variant_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
            $table->index(['branch_id', 'type', 'created_at']);
            $table->index('created_at'); // Para reportes por período
        });

        // ─────────────────────────────────────────────────────
        // TABLA: stock_transfers
        // Transferencias de stock entre sucursales
        // ─────────────────────────────────────────────────────
        //
        Schema::create('stock_transfers', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('from_branch_id')
                  ->constrained('branches')
                  ->restrictOnDelete();

            $table->foreignUuid('to_branch_id')
                  ->constrained('branches')
                  ->restrictOnDelete();

            $table->enum('status', ['pending', 'in_transit', 'received', 'cancelled'])
                  ->default('pending');

            $table->text('notes')->nullable();

            $table->foreignUuid('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('received_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('received_at')->nullable();
            $table->timestampsTz();
        });

        // Items de la transferencia
        Schema::create('stock_transfer_items', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('transfer_id')
                  ->constrained('stock_transfers')
                  ->cascadeOnDelete();

            $table->foreignUuid('product_variant_id')
                  ->constrained('product_variants')
                  ->restrictOnDelete();

            $table->decimal('quantity_sent', 10, 3);
            $table->decimal('quantity_received', 10, 3)->nullable(); // null hasta que se recibe
            $table->text('notes')->nullable();

            $table->timestampsTz();

            $table->index('transfer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventory');
    }
};
