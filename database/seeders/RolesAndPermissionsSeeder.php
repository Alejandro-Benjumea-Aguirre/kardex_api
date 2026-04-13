<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Role, Permission};


class RolesAndPermissionsSeeder extends Seeder
{
    // Definición de todos los permisos del sistema
    // Formato: 'módulo' => [ ['acción', 'nombre legible', 'descripción'] ]
    private array $permissionsMatrix = [

        'products' => [
            ['read',    'Ver Productos',      'Listar y ver detalle de productos'],
            ['create',  'Crear Productos',    'Crear nuevos productos en el catálogo'],
            ['update',  'Editar Productos',   'Modificar productos existentes'],
            ['delete',  'Eliminar Productos', 'Eliminar productos del catálogo'],
            ['import',  'Importar Productos', 'Importar productos desde Excel/CSV'],
            ['export',  'Exportar Productos', 'Exportar catálogo a Excel/PDF'],
        ],

        'sales' => [
            ['read',     'Ver Ventas',         'Listar y ver detalle de ventas'],
            ['create',   'Crear Ventas',        'Registrar nuevas ventas en el POS'],
            ['void',     'Anular Ventas',       'Anular ventas ya registradas'],
            ['refund',   'Hacer Devoluciones',  'Procesar devoluciones de clientes'],
            ['discount', 'Aplicar Descuentos',  'Aplicar descuentos adicionales en ventas'],
        ],

        'inventory' => [
            ['read',     'Ver Inventario',        'Ver stock actual por sucursal'],
            ['manage',   'Gestionar Inventario',  'Ajustar stock manualmente'],
            ['transfer', 'Transferir Stock',      'Transferir stock entre sucursales'],
            ['adjust',   'Ajustar Stock',         'Registrar mermas y ajustes'],
        ],

        'purchases' => [
            ['read',    'Ver Compras',            'Ver órdenes de compra y recepciones'],
            ['create',  'Crear Órdenes',          'Crear órdenes de compra a proveedores'],
            ['approve', 'Aprobar Órdenes',        'Aprobar y enviar órdenes de compra'],
            ['receive', 'Recibir Mercancía',      'Registrar recepción de mercancía'],
        ],

        'customers' => [
            ['read',   'Ver Clientes',    'Listar y ver detalle de clientes'],
            ['create', 'Crear Clientes',  'Registrar nuevos clientes'],
            ['update', 'Editar Clientes', 'Modificar datos de clientes'],
            ['delete', 'Eliminar Clientes', 'Eliminar clientes del sistema'],
        ],

        'suppliers' => [
            ['read',   'Ver Proveedores',    'Listar y ver detalle de proveedores'],
            ['create', 'Crear Proveedores',  'Registrar nuevos proveedores'],
            ['update', 'Editar Proveedores', 'Modificar datos de proveedores'],
            ['delete', 'Eliminar Proveedores', 'Eliminar proveedores del sistema'],
        ],

        'users' => [
            ['read',         'Ver Usuarios',        'Listar y ver detalle de usuarios'],
            ['create',       'Crear Usuarios',      'Crear nuevos usuarios en la empresa'],
            ['update',       'Editar Usuarios',     'Modificar datos de usuarios'],
            ['delete',       'Eliminar Usuarios',   'Desactivar usuarios del sistema'],
            ['assign-roles', 'Asignar Roles',       'Asignar y revocar roles a usuarios'],
        ],

        'roles' => [
            ['read',   'Ver Roles',    'Listar roles y sus permisos'],
            ['create', 'Crear Roles',  'Crear nuevos roles personalizados'],
            ['update', 'Editar Roles', 'Modificar permisos de roles existentes'],
            ['delete', 'Eliminar Roles', 'Eliminar roles personalizados'],
        ],

        'reports' => [
            ['view',   'Ver Reportes',      'Acceder al módulo de reportes'],
            ['export', 'Exportar Reportes', 'Exportar reportes a Excel/PDF'],
        ],

        'settings' => [
            ['manage', 'Gestionar Configuración', 'Administrar la configuración de la empresa'],
        ],

        // Permisos de sistema — solo super_admin
        'system' => [
            ['access', 'Acceso al Sistema', 'Acceder al panel de administración del sistema'],
            ['manage', 'Gestionar Sistema',  'Gestionar empresas, planes y configuración global'],
        ],
    ];

    // Definición de roles con sus permisos
    // '*' = todos los permisos del módulo
    private array $rolesMatrix = [

        'super_admin' => [
            'display_name' => 'Super Administrador',
            'description'  => 'Acceso total al sistema. Solo para el equipo de soporte.',
            'is_system'    => true,
            'is_default'   => false,
            // Super admin tiene TODOS los permisos
            'permissions'  => ['*'],
        ],

        'admin' => [
            'display_name' => 'Administrador',
            'description'  => 'Administrador de la empresa. Acceso total excepto gestión del sistema.',
            'is_system'    => true,
            'is_default'   => false,
            'permissions'  => [
                'products:read',    'products:create',  'products:update',
                'products:delete',  'products:import',  'products:export',
                'sales:read',       'sales:create',     'sales:void',
                'sales:refund',     'sales:discount',
                'inventory:read',   'inventory:manage', 'inventory:transfer', 'inventory:adjust',
                'purchases:read',   'purchases:create', 'purchases:approve',  'purchases:receive',
                'customers:read',   'customers:create', 'customers:update',   'customers:delete',
                'suppliers:read',   'suppliers:create', 'suppliers:update',   'suppliers:delete',
                'users:read',       'users:create',     'users:update',       'users:delete',
                'users:assign-roles',
                'roles:read',       'roles:create',     'roles:update',       'roles:delete',
                'reports:view',     'reports:export',
                'settings:manage',
                // NO tiene system:* — eso es solo para super_admin
            ],
        ],

        'manager' => [
            'display_name' => 'Gerente',
            'description'  => 'Gerente de sucursal. Gestiona operaciones y personal.',
            'is_system'    => true,
            'is_default'   => false,
            'permissions'  => [
                'products:read',    'products:create',  'products:update',   'products:export',
                'sales:read',       'sales:create',     'sales:void',        'sales:discount',
                'inventory:read',   'inventory:manage', 'inventory:transfer',
                'purchases:read',   'purchases:create', 'purchases:receive',
                'customers:read',   'customers:create', 'customers:update',
                'suppliers:read',
                'users:read',
                'reports:view',     'reports:export',
            ],
        ],

        'cashier' => [
            'display_name' => 'Cajero',
            'description'  => 'Cajero del POS. Solo puede registrar ventas y ver productos.',
            'is_system'    => true,
            'is_default'   => true,  // Rol por defecto para nuevos usuarios
            'permissions'  => [
                'products:read',
                'sales:read',    'sales:create',
                'customers:read', 'customers:create',
            ],
        ],

        'inventory_manager' => [
            'display_name' => 'Encargado de Inventario',
            'description'  => 'Gestiona el inventario y las compras a proveedores.',
            'is_system'    => true,
            'is_default'   => false,
            'permissions'  => [
                'products:read',    'products:create',   'products:update',  'products:import',
                'inventory:read',   'inventory:manage',  'inventory:transfer', 'inventory:adjust',
                'purchases:read',   'purchases:create',  'purchases:receive',
                'suppliers:read',   'suppliers:create',  'suppliers:update',
                'reports:view',
            ],
        ],

        'viewer' => [
            'display_name' => 'Solo Lectura',
            'description'  => 'Acceso de solo lectura para auditoría o supervisión.',
            'is_system'    => true,
            'is_default'   => false,
            'permissions'  => [
                'products:read',
                'sales:read',
                'inventory:read',
                'purchases:read',
                'customers:read',
                'suppliers:read',
                'users:read',
                'reports:view',
            ],
        ],
    ];

    public function run(): void
    {
        $this->command->info('Creando permisos del sistema...');
        $allPermissions = $this->seedPermissions();

        $this->command->info('Creando roles del sistema...');
        $this->seedRoles($allPermissions);

        $this->command->info('✅ Roles y permisos del sistema cargados correctamente.');
        $this->command->table(
            ['Rol', 'Permisos'],
            collect($this->rolesMatrix)->map(fn($r, $name) => [
                $r['display_name'],
                $r['permissions'] === ['*'] ? 'Todos' : count($r['permissions']),
            ])->values()->all()
        );
    }

    // ─── SEED DE PERMISOS ─────────────────────────────────

    private function seedPermissions(): array
    {
        $allPermissions = [];
        $sortOrder      = 0;

        foreach ($this->permissionsMatrix as $module => $actions) {
            foreach ($actions as [$action, $displayName, $description]) {

                $name = "{$module}:{$action}";

                $permission = Permission::firstOrCreate(
                    // Buscar por: nombre + sin empresa (global)
                    ['name' => $name, 'company_id' => null],
                    // Crear con estos datos si no existe:
                    [
                        'display_name' => $displayName,
                        'description'  => $description,
                        'module'       => $module,
                        'sort_order'   => $sortOrder++,
                        'is_system'    => true,
                        'is_active'    => true,
                    ]
                );

                $allPermissions[$name] = $permission;
            }
        }

        return $allPermissions;
    }

    // ─── SEED DE ROLES ────────────────────────────────────

    private function seedRoles(array $allPermissions): void
    {
        foreach ($this->rolesMatrix as $name => $config) {

            $role = Role::firstOrCreate(
                ['name' => $name, 'company_id' => null],
                [
                    'display_name' => $config['display_name'],
                    'description'  => $config['description'],
                    'is_system'    => $config['is_system'],
                    'is_default'   => $config['is_default'],
                    'is_active'    => true,
                ]
            );

            // Sincronizar permisos del rol
            // sync() reemplaza TODOS los permisos actuales con los nuevos.
            // Esto garantiza que al re-ejecutar el seeder,
            // los permisos quedan exactamente como se define aquí.
            if ($config['permissions'] === ['*']) {
                // Super admin: todos los permisos
                $role->permissions()->sync(
                    collect($allPermissions)->pluck('id')
                );
            } else {
                $permissionIds = collect($config['permissions'])
                    ->map(fn($permName) => $allPermissions[$permName]?->id)
                    ->filter()
                    ->values()
                    ->all();

                $role->permissions()->sync($permissionIds);
            }

            $this->command->line("  → Rol '{$role->display_name}' con " . count($config['permissions'] === ['*'] ? $allPermissions : $config['permissions']) . " permisos");
        }
    }
}
