<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permisos de la pantalla nueva "Presupuesto" (asignación de presupuesto por
 * usuario/cajero y mes), granulares igual que Gestión (agregar-gestion,
 * editar-gestion, ...).
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        $permisos = ['presupuesto', 'agregar-presupuesto', 'editar-presupuesto', 'eliminar-presupuesto'];

        $superUsuarioId = DB::table($tableNames['roles'])->where('name', 'superUsuario')->value('id');

        foreach ($permisos as $permiso) {
            $permissionId = DB::table($tableNames['permissions'])->insertGetId([
                'name' => $permiso,
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // El rol superUsuario no se puede editar desde Roles (por seguridad,
            // el botón de edición está oculto para ese rol) — se le asigna acá
            // directamente, igual que se hizo con `bandeja-pago` en
            // 2026_07_30_090000_add_bandeja_pago_permission.php.
            if ($superUsuarioId) {
                DB::table($tableNames['role_has_permissions'])->insert([
                    'permission_id' => $permissionId,
                    'role_id' => $superUsuarioId,
                ]);
            }
        }

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

        DB::table($tableNames['permissions'])
            ->whereIn('name', ['presupuesto', 'agregar-presupuesto', 'editar-presupuesto', 'eliminar-presupuesto'])
            ->delete();
    }
};
