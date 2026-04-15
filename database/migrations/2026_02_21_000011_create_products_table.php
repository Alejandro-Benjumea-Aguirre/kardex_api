<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 004 — catálogo de productos
// Tablas: categories, products, product_variants, barcodes
//
// CONCEPTO: Catálogo compartido vs Stock por sucursal
// ═══════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────────────
        // TABLA: categories
        // ─────────────────────────────────────────────────────
        //

        Schema::create('categories', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();

            $table->uuid('parent_id')->nullable();

            $table->string('name', 100);
            $table->string('slug', 120);
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestampsTz();

            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'parent_id']);
            $table->index(['company_id', 'is_active', 'sort_order']);
        });

        Schema::table('categories', function (Blueprint $table) {
        $table->foreign('parent_id')
              ->references('id')
              ->on('categories')
              ->nullOnDelete();
        });

        // ─────────────────────────────────────────────────────
        // TABLA: products
        // ─────────────────────────────────────────────────────
        Schema::create('products', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();

            $table->foreignUuid('category_id')
                  ->nullable()
                  ->constrained('categories')
                  ->nullOnDelete();

            // ─── IDENTIFICACIÓN ───────────────────────────────
            $table->string('name', 200);
            $table->string('slug', 220);
            $table->text('description')->nullable();

            $table->string('sku', 100);

            // ─── PRECIOS ─────────────────────────────────────
            //
            $table->decimal('cost_price', 12, 2)->default(0);    // Precio de costo
            $table->decimal('sale_price', 12, 2);                // Precio de venta
            $table->decimal('min_price', 12, 2)->nullable();     // Precio mínimo permitido (descuentos)

            // ─── IMPUESTOS ────────────────────────────────────
            //
            $table->boolean('price_includes_tax')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(19.00); // IVA default 19%

            // ─── TIPO DE PRODUCTO ─────────────────────────────
            //
            $table->enum('type', ['physical', 'service', 'digital'])->default('physical');

            // ─── VARIANTES ────────────────────────────────────
            //
            $table->boolean('has_variants')->default(false);

            // ─── IMÁGENES ─────────────────────────────────────
            //
            $table->jsonb('images')->default('[]');

            // ─── ATRIBUTOS EXTRAS ─────────────────────────────
            //

            $table->jsonb('attributes')->default('{}');

            $table->boolean('is_active')->default(true);

            $table->timestampsTz();
            $table->softDeletes();

            // ─── ÍNDICES ─────────────────────────────────────
            $table->unique(['company_id', 'sku']);
            $table->unique(['company_id', 'slug']);
            $table->index(['company_id', 'category_id', 'is_active']);
            $table->index(['company_id', 'type', 'is_active']);

        });

        // ─── FULL-TEXT SEARCH ─────────────────────────────
        //
        DB::statement('ALTER TABLE products ADD COLUMN search_vector tsvector NULL');

        DB::statement('CREATE INDEX products_search_idx ON products USING GIN(search_vector)');

        DB::statement("
            CREATE OR REPLACE FUNCTION products_search_vector_update()
            RETURNS TRIGGER AS $$
            BEGIN
                -- 'spanish' para stemming en español
                -- to_tsvector procesa el texto y genera el vector
                -- || concatena vectores de diferentes campos
                -- setweight le da más importancia al nombre que a la descripción
                NEW.search_vector :=
                    setweight(to_tsvector('spanish', coalesce(NEW.name, '')), 'A') ||
                    setweight(to_tsvector('spanish', coalesce(NEW.sku,  '')), 'B') ||
                    setweight(to_tsvector('spanish', coalesce(NEW.description, '')), 'C');
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ");

        DB::statement("
            CREATE TRIGGER products_search_vector_trigger
            BEFORE INSERT OR UPDATE ON products
            FOR EACH ROW EXECUTE FUNCTION products_search_vector_update();
        ");

        // ─────────────────────────────────────────────────────
        // TABLA: product_variants
        // ─────────────────────────────────────────────────────
        //
        Schema::create('product_variants', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete();

            $table->string('name', 200); // "Talla M - Color Azul" o "Default"
            $table->string('sku', 100)->nullable(); // Puede heredar el SKU del producto

            $table->decimal('cost_price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();

            $table->jsonb('attributes')->default('{}');

            $table->string('image_url')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false); // Variante predeterminada

            $table->timestampsTz();

            $table->unique(['product_id', 'sku']);
            $table->index(['product_id', 'is_active']);
        });

        // ─────────────────────────────────────────────────────
        // TABLA: barcodes
        // ─────────────────────────────────────────────────────
        //
        Schema::create('barcodes', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            $table->foreignUuid('product_variant_id')
                  ->constrained('product_variants')
                  ->cascadeOnDelete();

            $table->string('code', 100)->unique(); // Único globalmente

            $table->enum('type', ['ean13', 'ean8', 'upc', 'qr', 'custom'])
                  ->default('ean13');

            $table->boolean('is_primary')->default(false); // ¿Es el código principal?

            $table->timestampsTz();

            $table->index('code'); // La búsqueda por código es la más frecuente
        });
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS products_search_vector_trigger ON products');
        DB::statement('DROP FUNCTION IF EXISTS products_search_vector_update');

        Schema::dropIfExists('barcodes');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
