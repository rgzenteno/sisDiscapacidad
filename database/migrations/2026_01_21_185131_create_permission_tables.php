<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        if (empty($tableNames)) {
            throw new \Exception('Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }
        if ($teams && empty($columnNames['team_foreign_key'] ?? null)) {
            throw new \Exception('Error: team_foreign_key on config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        }

        Schema::create($tableNames['permissions'], function (Blueprint $table) {
            //$table->engine('InnoDB');
            $table->bigIncrements('id'); // permission id
            $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
            $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
            $table->boolean('super_only')->default(false);
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], function (Blueprint $table) use ($teams, $columnNames) {
            //$table->engine('InnoDB');
            $table->bigIncrements('id'); // role id
            if ($teams || config('permission.testing')) { // permission.testing is a fix for sqlite testing
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');       // For MyISAM use string('name', 225); // (or 166 for InnoDB with Redundant/Compact row format)
            $table->string('guard_name'); // For MyISAM use string('guard_name', 25);
            $table->timestamps();
            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');

                $table->primary(
                    [$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );
            } else {
                $table->primary(
                    [$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary'
                );
            }
        });

        Schema::create($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);

            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on($tableNames['roles'])
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');

                $table->primary(
                    [$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            } else {
                $table->primary(
                    [$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary'
                );
            }
        });

        Schema::create($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id') // permission id
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id') // role id
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));

        DB::table($tableNames['permissions'])->insert([
            // General
            ['name' => 'general', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'importar-general', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'agregar-general', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'pendiente-general', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Beneficiario
            ['name' => 'beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'crear-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editar-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'asignaTutor-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'estado-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'crearCarnet-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editarCarnet-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'distrito-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'discapacidad-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'importar-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reporte-beneficiario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Tutores
            ['name' => 'tutor', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'crear-tutor', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
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
            ['name' => 'crearGestion-gestion', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editarGestion-gestion', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'crearMes-gestion', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editarMes-gestion', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reporteGestion-gestion', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reporteMes-gestion', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Usuarios
            ['name' => 'usuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'crear-usuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editar-usuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'resert-usuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Roles
            ['name' => 'roles', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'agregar-roles', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'editar-roles', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],

            // Logs/Auditoría
            ['name' => 'superusuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'logs-superusuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()], // en vez de actividad-logs
            ['name' => 'avanzada-superusuario', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()], // en vez de administracion-avanzada
        ]);

        // Crear el rol de Administrador
        $adminRoleId = DB::table($tableNames['roles'])->insertGetId([
            'name' => 'superUsuario',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Obtener todos los permisos recién creados
        $permissions = DB::table($tableNames['permissions'])->pluck('id');

        // Asignar todos los permisos al rol Administrador
        $rolePermissions = [];
        foreach ($permissions as $permissionId) {
            $rolePermissions[] = [
                'permission_id' => $permissionId,
                'role_id' => $adminRoleId,
            ];
        }

        DB::table($tableNames['role_has_permissions'])->insert($rolePermissions);

        // Marcar permisos exclusivos de superUsuario
        DB::table($tableNames['permissions'])
            ->whereIn('name', [
                'superusuario',
                'logs-superusuario',
                'avanzada-superusuario'
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

        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);
    }
};
