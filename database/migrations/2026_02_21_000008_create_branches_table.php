<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 002 — branches (sucursales)
//
// CONCEPTO: La unidad operativa del POS
// ═══════════════════════════════════════════════════════════
//
// Una branch es una tienda física o punto de venta.
// Una empresa puede tener 1 o N sucursales.
// Cada sucursal tiene:
//   - Su propio inventario (tabla inventory)
//   - Sus propias ventas (tabla sales)
//   - Sus propios empleados asignados
//   - Su propia caja/caja registradora
//
// REGLA DE DISEÑO CRÍTICA:
// Todo registro operativo (ventas, compras, movimientos)
// tiene branch_id. Nunca company_id directo.
// La jerarquía es: company → branch → operaciones
// ═══════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── FOREIGN KEY: company_id ──────────────────────
            //
            // foreignUuid() + constrained() hace dos cosas:
            // 1. Crea la columna company_id de tipo UUID
            // 2. Agrega la FOREIGN KEY CONSTRAINT que garantiza
            //    que no puede existir una sucursal sin empresa.
            //
            // cascadeOnDelete(): Si se elimina la empresa,
            // se eliminan todas sus sucursales automáticamente.
            //
            // Alternativas:
            //   nullOnDelete()   → company_id = NULL (no aplica aquí)
            //   restrictOnDelete() → ERROR si intentás borrar empresa con sucursales
            //
            // ¿Por qué cascadeOnDelete aquí?
            // Una sucursal no tiene sentido sin su empresa.
            // Si la empresa se da de baja del sistema, todo su
            // contenido debe eliminarse (o archivarse, ver softDeletes).
            //
            // ERROR COMÚN: Olvidar las FK constraints y depender
            // solo de la aplicación para mantener integridad.
            // La DB debe ser la última línea de defensa.
            $table->foreignUuid('company_id')
                  ->constrained('companies')
                  ->cascadeOnDelete();

            // ─── DATOS BÁSICOS ────────────────────────────────
            $table->string('name', 100);

            // Código interno de la sucursal: "SUC-001", "CENTRO", etc.
            // Único dentro de la empresa (no globalmente)
            $table->string('code', 20);

            // ─── DIRECCIÓN ───────────────────────────────────
            // Almacenamos dirección como texto libre + coordenadas separadas.
            //
            // ¿Por qué no un solo campo JSON?
            // Porque las coordenadas las vamos a usar en queries
            // geoespaciales potenciales (ej: "sucursales cerca de mí").
            // Tener lat/lng en columnas separadas permite índices.
            $table->string('address', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('country', 2)->default('CO'); // ISO 3166-1 alpha-2

            // DECIMAL para coordenadas geográficas:
            // DECIMAL(10, 8) → 10 dígitos totales, 8 decimales
            // Eso da precisión de ~1mm, más que suficiente.
            //
            // ¿Por qué no FLOAT?
            // FLOAT tiene errores de punto flotante.
            // 4.123456789 en FLOAT puede guardarse como 4.12345678901234
            // En coordenadas eso no importa, pero DECIMAL es más correcto.
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();  // 11 porque longitude va -180 a 180

            // ─── CONTACTO ────────────────────────────────────
            $table->string('phone', 20)->nullable();
            $table->string('email', 254)->nullable();

            // ─── CONFIGURACIÓN POR SUCURSAL ───────────────────
            //
            // Algunas sucursales pueden tener configuraciones
            // distintas a la empresa. Por ejemplo, diferentes
            // horarios de atención o impresoras de tickets.
            //
            // JSONB para flexibilidad de configuración.
            $table->jsonb('settings')->default(json_encode([
                'opening_time'     => '08:00',
                'closing_time'     => '20:00',
                'receipt_printer'  => null,
                'allow_credit'     => false,  // ¿Permite ventas a crédito?
            ]));

            // ─── NUMERACIÓN DE DOCUMENTOS ─────────────────────
            //
            // Cada sucursal tiene su propia numeración de facturas.
            // Este contador se incrementa con cada venta.
            //
            // IMPORTANTE: Esto NO se hace con auto_increment.
            // Se hace con SELECT ... FOR UPDATE en una transacción
            // para evitar duplicados en concurrencia.
            // (Lo explicamos cuando lleguemos a ventas)
            $table->unsignedBigInteger('invoice_counter')->default(0);

            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_main')->default(false); // Sucursal principal de la empresa

            $table->timestampsTz();
            $table->softDeletes(); // deleted_at — nunca borrar sucursales con historial

            // ─── ÍNDICES ─────────────────────────────────────
            //
            // CONCEPTO DE ÍNDICES:
            // Un índice es como el índice de un libro. Sin él,
            // la DB lee toda la tabla para encontrar un registro.
            // Con él, va directo al registro.
            //
            // Cuándo crear índices:
            //   ✅ Columnas que usás en WHERE frecuentemente
            //   ✅ Columnas de JOIN (foreign keys)
            //   ✅ Columnas de ORDER BY en queries grandes
            //   ❌ Columnas que raramente filtrás
            //   ❌ Tablas pequeñas (< 1000 filas, el índice no ayuda)
            //
            // Costo de los índices:
            //   - Ralentizan INSERT/UPDATE/DELETE (hay que actualizar el índice)
            //   - Aceleran SELECT (el beneficio casi siempre supera el costo)
            //
            // Índice compuesto: code + company_id
            // Porque la búsqueda típica es "dame la sucursal con código
            // 'SUC-001' de la empresa X". Nunca buscarías solo por code.
            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']); // Para listar sucursales activas
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
