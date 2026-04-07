<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 005 — permissions
//
// CONCEPTO: ¿Qué es un Permiso?
// ═══════════════════════════════════════════════════════════
//
// Un permiso es la unidad mínima de autorización.
// Responde la pregunta: "¿Puede este usuario hacer X?"
//
// CONVENCIÓN DE NOMBRES: módulo:acción
//   product:create    → crear productos
//   product:read      → leer/listar productos
//   product:update    → editar productos
//   product:delete    → eliminar productos
//   sale:create       → crear ventas
//   inventory:manage  → gestionar inventario
//
// Esta convención viene de diseño de APIs y OAuth scopes.
// Es legible, predecible y fácil de escalar.
//
// ─── PERMISOS GLOBALES vs POR EMPRESA ─────────────────────
//
// Al igual que los roles, los permisos pueden ser:
//
// GLOBALES (company_id = NULL):
//   Permisos base que existen para todas las empresas.
//   Son los que definís al hacer el seed del sistema.
//   Ejemplos: product:create, sale:read, inventory:manage
//
// POR EMPRESA (company_id = UUID):
//   Permisos custom que una empresa específica crea.
//   Útil cuando una empresa tiene funcionalidades únicas.
//   Ejemplo: "empresa farmacéutica" crea "prescription:approve"
//
// ─── GRANULARIDAD DE PERMISOS ─────────────────────────────
//
// ¿Qué tan granular debe ser un permiso?
//
// MUY GRANULAR (un permiso por campo):
//   product:update:price   → solo cambiar precio
//   product:update:name    → solo cambiar nombre
//   ✅ Control máximo
//   ❌ Explosión de permisos, imposible de administrar
//
// MUY AMPLIO (un permiso por módulo):
//   product:manage → hace todo con productos
//   ✅ Simple
//   ❌ Sin control fino
//
// BALANCE (lo que hacemos — por acción CRUD + acciones especiales):
//   product:create, product:read, product:update, product:delete
//   sale:create, sale:read, sale:update, sale:void (anular)
//   report:view, report:export
//   ✅ Control suficiente para un POS
//   ✅ Manejable para el administrador
// ═══════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── SCOPE DEL PERMISO ────────────────────────────
            // Mismo patrón que roles: NULL = global, UUID = empresa
            $table->foreignUuid('company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->cascadeOnDelete();

            // ─── IDENTIFICACIÓN ───────────────────────────────
            //
            // name: identificador técnico usado en el código
            //   FORMATO: módulo:acción
            //   Ejemplos: 'product:create', 'sale:void', 'report:export'
            //
            // Este es el valor que se compara en las Gates de Laravel:
            //   $user->can('product:create')
            //   Gate::define('product:create', fn($user) => ...)
            $table->string('name', 150);
            $table->string('display_name', 200);
            $table->text('description')->nullable();

            // ─── ORGANIZACIÓN POR MÓDULO ──────────────────────
            //
            // module agrupa permisos en la UI de administración.
            // Sin esto, mostrar 50 permisos en una lista es caótico.
            // Con module podés agrupar: "Permisos de Ventas", etc.
            //
            // Ejemplos de módulos:
            //   'products'   → product:create, product:read, product:update, product:delete
            //   'sales'      → sale:create, sale:read, sale:void, sale:refund
            //   'inventory'  → inventory:read, inventory:manage, inventory:transfer
            //   'purchases'  → purchase:create, purchase:read, purchase:approve
            //   'reports'    → report:view, report:export
            //   'users'      → user:create, user:read, user:update, user:delete
            //   'settings'   → settings:manage
            //   'system'     → system:access (solo super_admin)
            $table->string('module', 100);

            // ─── ORDEN DE VISUALIZACIÓN ───────────────────────
            // Para mostrar los permisos en orden lógico en la UI
            // (primero read, luego create, luego update, luego delete)
            $table->unsignedSmallInteger('sort_order')->default(0);

            // ─── PERMISO DEL SISTEMA ──────────────────────────
            // Los permisos del sistema no pueden borrarse.
            // Se crean en el seeder y son la base del RBAC.
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
