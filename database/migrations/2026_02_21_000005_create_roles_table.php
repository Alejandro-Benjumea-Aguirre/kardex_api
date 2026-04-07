<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 004 — roles
//
// CONCEPTO: ¿Qué es un Rol?
// ═══════════════════════════════════════════════════════════
//
// Un rol es un nombre que agrupa permisos.
// En vez de asignarle 20 permisos individuales a cada cajero,
// creás el rol "Cajero" con esos 20 permisos, y luego
// asignás ese rol a todos los cajeros.
//
// Cambiar lo que puede hacer un cajero = editar el rol.
// Todos los cajeros heredan el cambio automáticamente.
//
// ─── ROLES POR EMPRESA vs ROLES GLOBALES ─────────────────
//
// Hay dos modelos:
//
// ROLES GLOBALES (company_id = NULL):
//   Existen a nivel del sistema entero.
//   Todas las empresas comparten los mismos roles.
//   ✅ Más simple — un solo set de roles para mantener
//   ❌ Rígido — no podés personalizar roles por empresa
//   → Usalo cuando: todas las empresas tienen la misma
//     estructura operativa (cadenas de tiendas idénticas)
//
// ROLES POR EMPRESA (company_id = UUID):
//   Cada empresa define sus propios roles.
//   ✅ Flexible — "Empresa A" puede tener un rol "Supervisor
//     de turno" que "Empresa B" no tiene
//   ✅ Cada empresa controla su propia seguridad
//   ❌ Más complejo — hay que seed roles para cada empresa nueva
//   → Usalo cuando: las empresas tienen estructuras diferentes
//
// MODELO HÍBRIDO (lo que hacemos):
//   Roles con company_id = NULL son "roles del sistema"
//   que aplican a todos los tenants (super_admin, etc.)
//   Roles con company_id = UUID son roles propios de esa empresa.
//   ✅ Lo mejor de ambos mundos
// ═══════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── SCOPE DEL ROL ────────────────────────────────
            //
            // nullable: rol global del sistema (NULL) o de empresa (UUID)
            //
            // Ejemplos roles globales (company_id = NULL):
            //   - super_admin: acceso a todas las empresas
            //
            // Ejemplos roles de empresa (company_id = UUID):
            //   - admin, manager, cajero, inventario, viewer
            //   - roles personalizados que cada empresa crea
            $table->foreignUuid('company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->cascadeOnDelete();

            // ─── IDENTIFICACIÓN DEL ROL ───────────────────────
            //
            // name: nombre técnico del rol, usado en código
            //   "admin", "cashier", "inventory_manager"
            //   Sin espacios, en minúsculas, con guiones bajos.
            //   Se usa en las gates de Laravel: $user->can('admin')
            //
            // display_name: nombre legible para el usuario final
            //   "Administrador", "Cajero", "Encargado de Inventario"
            //   Se muestra en la UI
            $table->string('name', 100);
            $table->string('display_name', 150);
            $table->text('description')->nullable();

            // ─── ROL PREDETERMINADO ────────────────────────────
            //
            // Cuando se crea un nuevo usuario en una empresa,
            // ¿qué rol se le asigna automáticamente?
            //
            // Solo puede haber UN rol default por empresa.
            // Esto se valida en la aplicación (no en DB con constraint
            // porque requeriría un partial unique index complejo).
            $table->boolean('is_default')->default(false);

            // ─── ROL DEL SISTEMA ──────────────────────────────
            //
            // Los roles del sistema (is_system = true) no pueden
            // ser editados ni borrados por los admins de empresa.
            // Solo el super_admin puede modificarlos.
            // Ejemplo: el rol "super_admin" no puede desaparecer.
            $table->boolean('is_system')->default(false);

            $table->boolean('is_active')->default(true);

            $table->timestampsTz();
            $table->softDeletes();

            // ─── ÍNDICES ─────────────────────────────────────
            //
            // name único dentro del scope de la empresa.
            // NULL y NULL NO son iguales en SQL (NULL != NULL),
            // entonces este UNIQUE funciona correctamente:
            //   - dos roles globales no pueden tener el mismo name
            //   - dos roles de la misma empresa no pueden tener el mismo name
            //   - una empresa puede tener un rol 'admin' aunque el global también exista
            $table->unique(['company_id', 'name']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
