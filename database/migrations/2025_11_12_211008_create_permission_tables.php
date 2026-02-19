<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        // Crear tabla de permisos
        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->boolean('super_only')->default(false);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        // Insertar los permisos
        DB::table($tableNames['permissions'])->insert([
            // General
            ['name' => 'general', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'importar-general', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'agregar-general', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'pendiente-general', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Beneficiario
            ['name' => 'beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'agregar-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editar-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'importar-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reporte-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Estado
            ['name' => 'estado', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'agregar-estado', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'eliminar-estado', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Carnet
            ['name' => 'carnet', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'agregar-carnet', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editar-carnet', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Tutores
            ['name' => 'tutor', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'agregar-tutor', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'asignar-tutor', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editar-tutor', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'tutorados-tutor', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Habilitados
            ['name' => 'habilitar', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'habilitar-habilitar', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'deshabilitar-habilitar', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Pagos
            ['name' => 'pago', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'registrar-pago', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'comprobante-pago', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Gestión
            ['name' => 'gestion', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'agregar-gestion', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editar-gestion', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reporte-gestion', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Mes
            ['name' => 'mes', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'agregar-mes', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editar-mes', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reporte-mes', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Usuarios
            ['name' => 'usuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'agregar-usuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editar-usuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Roles
            ['name' => 'roles', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'agregar-roles', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editar-roles', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Logs/Auditoría
            ['name' => 'superusuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'logs-superusuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'acciones-superusuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'distrito-superusuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'discapacidad-superusuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'restablecer-superusuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Marcar permisos exclusivos de superUsuario
        DB::table($tableNames['permissions'])
            ->whereIn('name', [
                'superusuario',
                'logs-superusuario',
                'acciones-superusuario',
                'distrito-superusuario',
                'discapacidad-superusuario',
                'passwordResert-superusuario'
            ])
            ->update(['super_only' => true]);

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        }

        Schema::drop($tableNames['permissions']);
    }
};
