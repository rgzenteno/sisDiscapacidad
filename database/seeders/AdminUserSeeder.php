<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::create([
            'nombre' => 'Admin',
            'apellido' => 'Sistema',
            'cargo' => 'Administrador del Sistema',
            'estado' => 0,
            'email' => 'admin@umadis.com',
            'password' => Hash::make('password'),
        ]);

        $admin->assignRole('Administrador');
    }
}
