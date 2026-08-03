<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permiso propio para suspender/reactivar pagos (Persona/listaHabilitados.vue)
 * — separado de `registrar-pago` a propósito: cobrar y decidir retener el
 * beneficio de alguien son responsabilidades distintas, no todo cajero que
 * cobra debería poder suspender pagos.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        $permissionId = DB::table($tableNames['permissions'])->insertGetId([
            'name' => 'suspender-pago',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // El rol superUsuario no se puede editar desde Roles (por seguridad,
        // el botón de edición está oculto para ese rol) — se le asigna acá
        // directamente, igual que se hizo con todos los permisos originales
        // en 2026_01_21_185131_rol.php.
        $superUsuarioId = DB::table($tableNames['roles'])->where('name', 'superUsuario')->value('id');

        if ($superUsuarioId) {
            DB::table($tableNames['role_has_permissions'])->insert([
                'permission_id' => $permissionId,
                'role_id' => $superUsuarioId,
            ]);
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

        DB::table($tableNames['permissions'])->where('name', 'suspender-pago')->delete();
    }
};
