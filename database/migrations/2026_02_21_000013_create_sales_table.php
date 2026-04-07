<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 006 — customers, sales, sale_items, payments
//
// CONCEPTO: El flujo de una venta y por qué es tan complejo
// ═══════════════════════════════════════════════════════════
//

return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────
        // TABLA: customers
        // ─────────────────────────────────────────────────────
        Schema::create('customers', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();

            $table->string('first_name', 50)->nullable(); // nullable para clientes anónimos
            $table->string('last_name', 50)->nullable();

            // Tipo de documento de identificación
            $table->enum('document_type', ['cc', 'nit', 'ce', 'passport', 'other'])->nullable();
            $table->string('document_number', 20)->nullable();

            $table->string('email', 254)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address', 255)->nullable();

            // Para clientes empresariales (NIT)
            $table->string('company_name', 100)->nullable();

            $table->unsignedInteger('total_purchases')->default(0);
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->timestampTz('last_purchase_at')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletes();

            // Número de documento único por empresa
            $table->unique(['company_id', 'document_type', 'document_number']);
            $table->index(['company_id', 'email']);
        });

        // ─────────────────────────────────────────────────────
        // TABLA: sales
        // El documento de venta (header)
        // ─────────────────────────────────────────────────────
        Schema::create('sales', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('branch_id')
                  ->constrained('branches')
                  ->restrictOnDelete(); // No borrar sucursal con ventas

            // Cliente opcional — puede ser venta sin identificar cliente
            $table->foreignUuid('customer_id')
                  ->nullable()
                  ->constrained('customers')
                  ->nullOnDelete();

            // Vendedor que realizó la venta
            $table->foreignUuid('seller_id')
                  ->constrained('users')
                  ->restrictOnDelete();

            // ─── NÚMERO DE FACTURA ────────────────────────────
            //
            // "FAC-001-000001" = prefijo + código sucursal + número secuencial
            //
            $table->string('invoice_number', 30)->unique();

            // ─── ESTADO DE LA VENTA ───────────────────────────
            //
            $table->enum('status', ['draft', 'completed', 'cancelled', 'refunded'])
                  ->default('draft');

            // ─── TOTALES ─────────────────────────────────────
            //
            $table->decimal('subtotal', 12, 2);     // Suma de items sin impuestos
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);         // Total final

            // ─── DESCUENTO ────────────────────────────────────
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('discount_value', 8, 2)->nullable(); // El valor del descuento aplicado

            // ─── NOTAS ───────────────────────────────────────
            $table->text('notes')->nullable();

            // ─── METADATA ────────────────────────────────────
            //
            $table->jsonb('metadata')->default('{}');

            $table->timestampsTz();

            // ─── ÍNDICES ─────────────────────────────────────
            $table->index(['branch_id', 'status', 'created_at']);
            $table->index(['branch_id', 'customer_id']);
            $table->index(['branch_id', 'seller_id']);
            $table->index('created_at'); // Para reportes por fecha
        });

        // ─────────────────────────────────────────────────────
        // TABLA: sale_items
        // El detalle de cada producto vendido
        // ─────────────────────────────────────────────────────
        //
        Schema::create('sale_items', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('sale_id')
                  ->constrained('sales')
                  ->cascadeOnDelete();

            $table->foreignUuid('product_variant_id')
                  ->constrained('product_variants')
                  ->restrictOnDelete();

            $table->string('product_name', 200);  // Snapshot del nombre
            $table->string('sku', 100);            // Snapshot del SKU

            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_cost', 12, 4);   // Costo al momento de la venta (para margen)
            $table->decimal('unit_price', 12, 2);  // Precio antes de descuento
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);    // quantity * unit_price - discount
            $table->decimal('total', 12, 2);       // subtotal + tax

            $table->timestampsTz();

            $table->index('sale_id');
            $table->index('product_variant_id'); // Para reportes: "ventas de este producto"
        });

        // ─────────────────────────────────────────────────────
        // TABLA: payments
        // Los pagos de cada venta
        // ─────────────────────────────────────────────────────
        //

        Schema::create('payments', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('sale_id')
                  ->constrained('sales')
                  ->cascadeOnDelete();

            $table->enum('method', [
                'cash',           // Efectivo
                'card_credit',    // Tarjeta crédito
                'card_debit',     // Tarjeta débito
                'transfer',       // Transferencia bancaria
                'nequi',          // Nequi (Colombia)
                'daviplata',      // Daviplata (Colombia)
                'credit',         // Crédito interno del establecimiento
                'other',
            ]);

            $table->decimal('amount', 12, 2);

            $table->decimal('cash_received', 12, 2)->nullable();
            $table->decimal('change_given', 12, 2)->nullable();

            $table->string('reference', 100)->nullable();

            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])
                  ->default('completed');

            $table->text('notes')->nullable();
            $table->timestampsTz();

            $table->index('sale_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
        Schema::dropIfExists('customers');
    }
};
