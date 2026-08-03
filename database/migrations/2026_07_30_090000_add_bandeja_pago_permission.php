<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Permiso propio para ver los tabs administrativos de BandejaPagos/index.vue
 * (Resumen General, No Pagados, Bajas) — separado de `acciones-superusuario`
 * a propósito: ese permiso también gatilla funciones exclusivas de
 * superUsuario en otras partes de la app (Roles, Persona, Gestion, User,
 * Header, etc.) y no se quiere delegar todo eso solo para que un
 * encargado/administrador pueda ver los tabs de pagos. La acción de
 * "Reactivar pago" (BandejaPagos/index.vue) sigue exclusiva de
 * `acciones-superusuario`, igual que antes.
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');

        $permissionId = DB::table($tableNames['permissions'])->insertGetId([
            'name' => 'bandeja-pago',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // El rol superUsuario no se puede editar desde Roles (por seguridad,
        // el botón de edición está oculto para ese rol) — se le asigna acá
        // directamente, igual que se hizo con `anular-pago` en
        // 2026_07_29_150000_add_anular_pago_permission.php.
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

        DB::table($tableNames['permissions'])->where('name', 'bandeja-pago')->delete();
    }
};
