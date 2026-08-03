<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permiso propio para anular pagos (BandejaPagos/index.vue, tab Total
 * Pagados) — separado de `superusuario` a propósito: se quiere poder
 * delegar "anular" a otros roles sin darles también "reactivar", que
 * tiene que seguir siendo exclusivo de superUsuario.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        $permissionId = DB::table($tableNames['permissions'])->insertGetId([
            'name' => 'anular-pago',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // El rol superUsuario no se puede editar desde Roles (por seguridad,
        // el botón de edición está oculto para ese rol) — se le asigna acá
        // directamente, igual que se hizo con `suspender-pago` en
        // 2026_07_26_130000_add_suspender_pago_permission.php.
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

        DB::table($tableNames['permissions'])->where('name', 'anular-pago')->delete();
    }
};
