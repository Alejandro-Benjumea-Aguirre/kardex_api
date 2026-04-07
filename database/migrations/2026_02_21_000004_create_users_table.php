<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ═══════════════════════════════════════════════════════════
// MIGRACIÓN 003 — users
//
// CONCEPTO: Usuarios en contexto multi-tenant
// ═══════════════════════════════════════════════════════════
//
// Un usuario en SalesPoint pertenece a UNA empresa y puede
// estar asignado a UNA O MUCHAS sucursales.
//
// La relación usuario ↔ sucursales es many-to-many:
//   - Un gerente puede supervisar múltiples sucursales
//   - Un empleado normalmente trabaja en una sola
//
// Esa relación M:M vive en la migración 004 → branch_users
//
// ROLES en SalesPoint:
//   super_admin → dueño del sistema (ve todas las empresas)
//   admin       → dueño de la empresa (ve todas sus sucursales)
//   manager     → gerente (ve sus sucursales asignadas)
//   cashier     → cajero (solo su sucursal, solo ventas)
//   inventory   → encargado de inventario
//   viewer      → solo lectura (reportes)
// ═══════════════════════════════════════════════════════════

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {

            $table->uuid('id')->primary()->default(DB::raw('uuid_generate_v4()'));

            // ─── MULTI-TENANT: company_id ─────────────────────
            //
            // nullable() porque el super_admin del sistema
            // no pertenece a ninguna empresa específica.
            //
            // nullOnDelete(): Si se borra la empresa, company_id = NULL.
            // El usuario queda huérfano pero no se borra — puede tener
            // historial de ventas y otras operaciones que deben conservarse.
            //
            // ¿Deberías tener una tabla separada para super_admins?
            // En sistemas grandes sí. Para SalesPoint, mantenerlos
            // en la misma tabla simplifica el código sin perder claridad.
            $table->foreignUuid('company_id')
                  ->nullable()
                  ->constrained('companies')
                  ->nullOnDelete();

            // ─── DATOS PERSONALES ─────────────────────────────
            $table->string('first_name', 50);
            $table->string('last_name', 50);

            // ─── EMAIL ────────────────────────────────────────
            //
            // Email único GLOBALMENTE (no por empresa).
            //
            // Alternativa: email único por empresa → UNIQUE(company_id, email)
            //   Ventaja: juan@gmail.com puede ser usuario en dos empresas
            //   Desventaja: el login necesita saber a cuál empresa loguear
            //               (¿pide la empresa en el formulario? Mala UX.)
            //
            // Decisión: único global → login simple con solo email+password.
            $table->string('email', 254)->unique();

            // ─── PASSWORD ────────────────────────────────────
            //
            // El cast 'hashed' en el Model Eloquent hashea
            // automáticamente al asignar: $user->password = '123'
            // → guarda bcrypt('123') sin que tenés que hacer nada.
            $table->string('password');

            // ─── ROL ──────────────────────────────────────────
            //
            // DECISIÓN ACTUALIZADA: No hay columna role aquí.
            //
            // Los roles viven en la tabla roles y se asignan
            // a usuarios via la tabla pivote user_roles.
            // Esto permite RBAC dinámico: crear/editar roles
            // y permisos desde el panel sin tocar código.
            //
            // Ver migración 004b → roles, permissions,
            //                      role_permissions, user_roles
            //
            // La desventaja es rendimiento: para saber si un usuario
            // puede hacer algo necesitás JOINs:
            //   users → user_roles → roles → role_permissions → permissions
            //
            // Solución: cachear los permisos del usuario en Redis
            // después del login. Cada request lee de caché, no de DB.
            // El caché se invalida cuando cambian los roles del usuario.

            // ─── PERFIL ───────────────────────────────────────
            $table->string('avatar_url')->nullable();
            $table->string('phone', 20)->nullable();

            // ─── ESTADO DE LA CUENTA ──────────────────────────
            $table->boolean('is_active')->default(true);
            $table->boolean('is_email_verified')->default(false);

            // ─── SEGURIDAD ────────────────────────────────────
            //
            // last_login_at sirve para:
            //   1. Detectar cuentas inactivas (no loguean hace 90 días)
            //   2. Mostrar "Último acceso: hace 2 horas" en el panel
            //   3. Auditar accesos desde IPs/horarios inusuales
            $table->timestampTz('last_login_at')->nullable();

            // ─── TIMESTAMPS ──────────────────────────────────
            //
            // timestampsTz() → SIEMPRE con zona horaria en PostgreSQL.
            // TIMESTAMP sin TZ tiene bugs en sistemas con múltiples
            // zonas horarias. PostgreSQL guarda internamente en UTC.
            $table->timestampsTz();

            // softDeletes() agrega la columna deleted_at.
            // Los usuarios nunca se borran físicamente porque tienen
            // historial de ventas, movimientos, y auditoría.
            // Un usuario "borrado" tiene deleted_at con timestamp.
            // Eloquent los excluye automáticamente de todas las queries.
            $table->softDeletes();

            // ─── ÍNDICES ─────────────────────────────────────
            //
            // email ya tiene UNIQUE que crea índice automáticamente.
            // No necesitás INDEX adicional en email.
            //
            // Índice compuesto company_id + role:
            // Query típica: "dame todos los managers de la empresa X"
            // WHERE company_id = ? AND role = 'manager'
            // Sin índice: full table scan de users completa.
            // Con índice compuesto: acceso directo.
            //
            // ¿Por qué company_id primero y no role primero?
            // Porque el filtro por company_id es más selectivo
            // (filtra a los usuarios de UNA empresa) que por role
            // (filtra todos los cashiers de TODAS las empresas).
            // PostgreSQL usa el índice más eficientemente cuando
            // la columna más selectiva va primero.
            // Ya no indexamos por role porque ese campo no existe aquí.
            // El índice de roles está en user_roles (migración 004b).
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
