<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;       // ← Spatie, no App\Models
use Spatie\Permission\Models\Permission; // ← Models, no Contracts

class AsignarPermisosRolSeeder extends Seeder
{
    public function run(): void
    {
        $superusuario = Role::findByName('superusuario');

        // Asignar TODOS los permisos
        $superusuario->syncPermissions(Permission::all());
    }
}
