<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 007 — suppliers, purchase_orders, purchases
//
// CONCEPTO: El ciclo de compras (Purchase Order → Receipt)
// ═══════════════════════════════════════════════════════════
//

return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────
        // TABLA: suppliers (proveedores)
        // ─────────────────────────────────────────────────────
        Schema::create('suppliers', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();

            // ─── IDENTIFICACIÓN LEGAL ─────────────────────────
            //

            $table->string('name', 150);             // Razón social
            $table->string('trade_name', 150)->nullable(); // Nombre comercial
            $table->string('nit', 20)->nullable();   // NIT sin dígito de verificación
            $table->string('nit_dv', 2)->nullable(); // Dígito de verificación

            // ─── CONTACTO ────────────────────────────────────
            //
            $table->string('contact_name', 100)->nullable();
            $table->string('email', 254)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('phone_alt', 20)->nullable();
            $table->string('website', 255)->nullable();

            // ─── DIRECCIÓN ───────────────────────────────────
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('country', 2)->default('CO');

            // ─── CONDICIONES COMERCIALES ──────────────────────
            //
            $table->unsignedSmallInteger('payment_terms_days')->default(0);

            $table->decimal('discount_rate', 5, 2)->default(0);

            $table->text('notes')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestampsTz();
            $table->softDeletes();

            $table->unique(['company_id', 'nit']);
            $table->index(['company_id', 'is_active']);
            $table->index('name'); // Búsqueda por nombre es frecuente
        });

        // ─────────────────────────────────────────────────────
        // TABLA: purchase_orders (órdenes de compra)
        // ─────────────────────────────────────────────────────
        //

        Schema::create('purchase_orders', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('branch_id')
                  ->constrained('branches')
                  ->restrictOnDelete();

            $table->foreignUuid('supplier_id')
                  ->constrained('suppliers')
                  ->restrictOnDelete();

            $table->foreignUuid('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            // ─── NUMERACIÓN ───────────────────────────────────
            //
            $table->string('order_number', 30)->unique();

            // ─── ESTADO DEL CICLO DE VIDA ─────────────────────
            //
            $table->enum('status', [
                'draft', 'sent', 'confirmed', 'partial', 'received', 'cancelled'
            ])->default('draft');

            // ─── TOTALES (snapshot al momento de crear la OC) ─
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // ─── CONDICIONES COPIADAS DEL PROVEEDOR ───────────
            // Se copian del proveedor pero se pueden editar por OC
            $table->unsignedSmallInteger('payment_terms_days')->default(0);

            // Fecha esperada de entrega
            $table->date('expected_date')->nullable();

            $table->text('notes')->nullable();

            // Número de referencia del proveedor (si da uno)
            $table->string('supplier_reference', 100)->nullable();

            $table->timestampsTz();
            $table->softDeletes();

            $table->index(['branch_id', 'status', 'created_at']);
            $table->index(['supplier_id', 'status']);
        });

        // Items de la orden de compra
        Schema::create('purchase_order_items', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('purchase_order_id')
                  ->constrained('purchase_orders')
                  ->cascadeOnDelete();

            $table->foreignUuid('product_variant_id')
                  ->constrained('product_variants')
                  ->restrictOnDelete();

            $table->string('product_name', 200);
            $table->string('sku', 100);

            $table->decimal('quantity_ordered', 10, 3);
            $table->decimal('quantity_received', 10, 3)->default(0); // Se va llenando con recepciones
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);

            $table->timestampsTz();

            $table->index('purchase_order_id');
        });

        // ─────────────────────────────────────────────────────
        // TABLA: purchases (recepciones de mercancía)
        // ─────────────────────────────────────────────────────
        //
        Schema::create('purchases', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('branch_id')
                  ->constrained('branches')
                  ->restrictOnDelete();

            $table->foreignUuid('supplier_id')
                  ->constrained('suppliers')
                  ->restrictOnDelete();

            // OC de origen — nullable porque puede ser compra directa
            $table->foreignUuid('purchase_order_id')
                  ->nullable()
                  ->constrained('purchase_orders')
                  ->nullOnDelete();

            $table->foreignUuid('received_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            // ─── NUMERACIÓN ───────────────────────────────────
            $table->string('receipt_number', 30)->unique();

            // ─── FACTURA DEL PROVEEDOR ────────────────────────
            //
            $table->string('supplier_invoice', 100)->nullable();
            $table->date('invoice_date')->nullable();

            // ─── ESTADO ──────────────────────────────────────
            //
            $table->enum('status', ['completed', 'returned'])->default('completed');

            // ─── TOTALES ─────────────────────────────────────
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            // ─── CONDICIONES DE PAGO ─────────────────────────
            $table->unsignedSmallInteger('payment_terms_days')->default(0);

            // ¿Cuándo vence el pago?
            // Se calcula: fecha_recepción + payment_terms_days
            $table->date('payment_due_date')->nullable();

            // ─── ESTADO DEL PAGO ──────────────────────────────
            //

            $table->enum('payment_status', ['pending', 'partial', 'paid'])
                  ->default('pending');

            $table->decimal('amount_paid', 12, 2)->default(0);

            $table->text('notes')->nullable();
            $table->timestampsTz();
            $table->softDeletes();

            $table->index(['branch_id', 'status', 'created_at']);
            $table->index(['branch_id', 'payment_status']); // "compras sin pagar"
            $table->index(['supplier_id', 'payment_status']);
            $table->index('payment_due_date'); // Para alertas de vencimiento
        });

        // Items de la recepción
        // Guardan lo que REALMENTE llegó (puede diferir de la OC)
        Schema::create('purchase_items', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('purchase_id')
                  ->constrained('purchases')
                  ->cascadeOnDelete();

            $table->foreignUuid('product_variant_id')
                  ->constrained('product_variants')
                  ->restrictOnDelete();

            // FK al item de la OC para poder comparar pedido vs recibido
            $table->foreignUuid('purchase_order_item_id')
                  ->nullable()
                  ->constrained('purchase_order_items')
                  ->nullOnDelete();

            // Snapshot del producto
            $table->string('product_name', 200);
            $table->string('sku', 100);

            $table->decimal('quantity', 10, 3);       // Cantidad recibida
            $table->decimal('unit_cost', 12, 4);       // Costo real de compra
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('total', 12, 2);

            // Fecha de vencimiento — CRÍTICO para productos perecederos
            // o medicamentos (lo usaremos también en MediCore)
            $table->date('expiry_date')->nullable();

            // Número de lote del fabricante
            $table->string('batch_number', 50)->nullable();

            $table->timestampsTz();

            $table->index('purchase_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('suppliers');
    }
};
