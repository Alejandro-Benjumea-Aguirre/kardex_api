<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // company_id nullable permite categorías globales (sin empresa)
        // que son visibles para todos los usuarios del sistema.
        DB::statement('ALTER TABLE categories ALTER COLUMN company_id DROP NOT NULL');
    }

    public function down(): void
    {
        // Solo funciona si no existen filas con company_id = NULL
        DB::statement('ALTER TABLE categories ALTER COLUMN company_id SET NOT NULL');
    }
};
