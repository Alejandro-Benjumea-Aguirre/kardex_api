<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class DefaultCategoriesSeeder extends Seeder
{
    private array $categories = [
        [
            'name'        => 'Alimentos',
            'slug'        => 'alimentos',
            'description' => 'Productos alimenticios en general',
            'sort_order'  => 1,
        ],
        [
            'name'        => 'Bebidas',
            'slug'        => 'bebidas',
            'description' => 'Bebidas frías, calientes y alcohólicas',
            'sort_order'  => 2,
        ],
        [
            'name'        => 'Limpieza',
            'slug'        => 'limpieza',
            'description' => 'Productos de limpieza del hogar',
            'sort_order'  => 3,
        ],
        [
            'name'        => 'Electrónica',
            'slug'        => 'electronica',
            'description' => 'Dispositivos y accesorios electrónicos',
            'sort_order'  => 4,
        ],
        [
            'name'        => 'Ropa',
            'slug'        => 'ropa',
            'description' => 'Prendas de vestir y accesorios',
            'sort_order'  => 5,
        ],
        [
            'name'        => 'Otros',
            'slug'        => 'otros',
            'description' => 'Productos varios sin categoría específica',
            'sort_order'  => 6,
        ],
    ];

    public function run(): void
    {
        $this->command->info('Creando categorías por defecto...');

        foreach ($this->categories as $data) {
            Category::firstOrCreate(
                ['slug' => $data['slug'], 'company_id' => null],
                [
                    'name'        => $data['name'],
                    'description' => $data['description'],
                    'sort_order'  => $data['sort_order'],
                    'parent_id'   => null,
                    'image_url'   => null,
                    'is_active'   => true,
                ]
            );
        }

        $this->command->info('✅ Categorías por defecto cargadas correctamente.');
    }
}
