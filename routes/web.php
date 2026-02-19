<?php

use App\Http\Controllers\CarnetController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigitalSignatureController;
use App\Http\Controllers\DropDownController;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\HabilitadoController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\NombreController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\RegistroGeneralController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TutorController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RoleController;
use Inertia\Inertia;

// Ruta principal - accesible para todos
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Rutas que requieren autenticación
Route::middleware(['auth', 'verified'])->group(function () {

    // Rutas para separar nombres con IA
    /* Route::prefix('nombres')->group(function () {
        Route::post('/separar', [NombreController::class, 'separar']);
        Route::post('/batch', [NombreController::class, 'separaBatch']);
        Route::get('/status', [NombreController::class, 'status']);
    }); */

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Roles
    Route::resource("roles", RoleController::class)
        ->middleware('permission:roles');

    // Perfil de usuario
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    // Gestión
    Route::prefix('gestion')->group(function () {
        Route::get('index', [GestionController::class, 'index'])
            ->middleware('permission:gestion')
            ->name('gestion.index');
        Route::get('reporte', [GestionController::class, 'reporte'])
            ->middleware('reporte-gestion')
            ->name('gestion.reporte');
        Route::post('store', [GestionController::class, 'store'])
            ->middleware('permission:agregar-gestion')
            ->name('gestion.store');
        Route::post('addMes', [GestionController::class, 'addMes'])
            ->middleware('permission:agregar-mes')
            ->name('gestion.addMes');
        Route::put('{id}', [GestionController::class, 'update'])
            ->middleware('permission:editar-gestion')
            ->name('gestion.update');
        Route::put('mes/{id}', [GestionController::class, 'updateMes'])
            ->middleware('permission:editar-mes')
            ->name('gestion.updateMes');
    });

    // Registro General
    Route::prefix('general')->group(function () {
        Route::get('index', [RegistroGeneralController::class, 'index'])
            ->middleware('permission:general')
            ->name('general.index');
        Route::post('importar', [RegistroGeneralController::class, 'importar'])
            ->middleware('permission:importar-general')
            ->name('general.importar.store');
        Route::get('change', [RegistroGeneralController::class, 'changeBeneficiary'])
            ->middleware('permission:cambiar-beneficiario')
            ->name('general.changeBeneficiary');
        Route::put('{id}/edit', [RegistroGeneralController::class, 'editRegistro'])
            ->middleware('permission:agregar-general')
            ->name('general.editRegistro');
        /* Route::post('{id}/preparar', [RegistroGeneralController::class, 'prepararHabilitar'])
            ->middleware('permission:preparar-habilitar')
            ->name('general.preparar'); */
    });

    // Persona
    Route::prefix('persona')->group(function () {
        Route::get('index', [PersonaController::class, 'index'])
            ->middleware('permission:beneficiario')
            ->name('persona.index');

        Route::post('store', [PersonaController::class, 'store'])
            ->middleware('permission:agregar-beneficiario')
            ->name('persona.store');

        Route::put('{id}', [PersonaController::class, 'update'])
            ->middleware('permission:editar-beneficiario')
            ->name('persona.update');

        Route::put('{id}/tutor', [PersonaController::class, 'updateTutor'])
            ->middleware('permission:asignar-tutor')
            ->name('persona.updateTutor');

        Route::post('estado', [PersonaController::class, 'estado'])
            ->middleware('permission:agregar-estado')
            ->name('persona.estado');

        Route::get('reporte', [PersonaController::class, 'reporte'])
            ->middleware('permission:reporte-beneficiario')
            ->name('persona.reporte');

        Route::get('showHabilitado/{id}', [HabilitadoController::class, 'showHabilitado'])
            ->middleware('permission:pago')
            ->name('persona.showHabilitado');

        Route::delete('persona/estado/{id}', [PersonaController::class, 'destroyEstado'])
            ->middleware('permission:eliminar-estado')
            ->name('persona.estado.eliminar');

        /*
        Route::get('importar', [PersonaController::class, 'mostrarFormulario'])
            ->middleware('permission:importar-personas')
            ->name('persona.importar');

        Route::get('reporteGeneral', [PersonaController::class, 'reporteGeneral'])
            ->middleware('permission:reporte-general-personas')
            ->name('persona.reporteGeneral');
        Route::post('importar', [PersonaController::class, 'importar'])
            ->middleware('permission:importar-personas')
            ->name('persona.importar.store');
        Route::delete('{id}', [PersonaController::class, 'destroy'])
            ->middleware('permission:eliminar-personas')
            ->name('persona.destroy'); */
    });

    // Tutor
    Route::prefix('tutor')->group(function () {
        Route::get('index', [TutorController::class, 'index'])
            ->middleware('permission:tutor')
            ->name('tutor.index');
        Route::post('store', [TutorController::class, 'store'])
            ->middleware('permission:agregar-tutor|agregar-general')
            ->name('tutor.store');
        Route::put('{id}', [TutorController::class, 'update'])
            ->middleware('permission:editar-tutor')
            ->name('tutor.update');
        Route::get('tutorados/{id}', [TutorController::class, 'tutorados'])
            ->middleware('permission:tutorados-tutor')
            ->name('tutor.tutorados');
    });

    // Logs
    Route::prefix('log')->group(function () {
        Route::get('index', [LogController::class, 'index'])
            ->middleware('permission:logs-superusuario')
            ->name('log.index');
    });

    // Configuración
    /*  Route::prefix('config')->group(function () {
        Route::get('config', [UserController::class, 'config'])
            ->middleware('permission:logs-superusuario')
            ->name('sistem.config');
    }); */

    // Carnet
    Route::prefix('carnet')->group(function () {
        Route::post('store', [CarnetController::class, 'store'])
            ->middleware('permission:agregar-carnet')
            ->name('carnet.store');
        Route::put('{id}', [CarnetController::class, 'update'])
            ->middleware('permission:editar-carnet')
            ->name('carnet.update');
    });

    // Habilitado
    Route::prefix('habilitado')->group(function () {
        Route::get('show/{id?}', [HabilitadoController::class, 'show'])
            ->middleware('permission:habilitar')
            ->name('persona.show');
        Route::get('show', [HabilitadoController::class, 'show'])
            ->middleware('permission:habilitar')
            ->name('habilitado.show');

        Route::post('store', [HabilitadoController::class, 'store'])
            ->middleware('permission:habilitar-habilitar')
            ->name('habilitado.store');
        Route::post('{id}', [HabilitadoController::class, 'edit'])
            ->middleware('permission:deshabilitar-habilitar')
            ->name('habilitado.edit');

        /*
        Route::get('index', [HabilitadoController::class, 'index'])
            ->middleware('permission:habilitar')
            ->name('habilitado.index'); */
    });

    // Historial Habilitado
    Route::prefix('historial')->group(function () {
        Route::post('store', [HabilitadoController::class, 'store'])
            ->middleware('permission:agregar-habilitados')
            ->name('historial.store');
    });

    // Pago
    Route::prefix('pago')->group(function () {
        Route::get('reporteLog', [PagoController::class, 'reporteLog'])
            ->middleware('permission:reporteGestion-gestion')
            ->name('pago.reporteLog');
        Route::post('store', [PagoController::class, 'store'])
            ->middleware('permission:registrar-pago')
            ->name('pago.store');
        Route::post('comp', [PagoController::class, 'comp'])
            ->middleware('permission:registrar-pago')
            ->name('pago.comp');

        /*  Route::get('index', [PagoController::class, 'index'])
            ->middleware('permission:pago')
            ->name('pago.index');
        Route::get('show/{id}', [PagoController::class, 'show'])
            ->middleware('permission:ver-pagos')
            ->name('pago.show');
        Route::get('reporte', [PagoController::class, 'reporte'])
            ->middleware('permission:reporte-pagos')
            ->name('pago.reporte');
        Route::get('reportePago', [PagoController::class, 'reportePago'])
            ->middleware('permission:reporte-pagos')
            ->name('pago.reportePago');
        Route::get('transferencia', [PagoController::class, 'transferencia'])
            ->middleware('permission:gestionar-transferencias')
            ->name('pago.transferencia'); */
    });

    // Reporte
    Route::prefix('reporte')->group(function () {
        Route::get('index', [ReporteController::class, 'index'])
            ->middleware('permission:ver-reportes')
            ->name('reporte.index');
    });

    Route::get('/reporte/buscar', [ReporteController::class, 'buscar'])
        ->middleware('permission:buscar-reportes')
        ->name('reporte.buscar');

    // Usuarios
    Route::prefix('usuario')->group(function () {
        Route::get('index', [UserController::class, 'index'])
            ->middleware('permission:usuario')
            ->name('usuario.index');
        Route::post('store', [UserController::class, 'store'])
            ->middleware('permission:agregar-usuario')
            ->name('usuario.store');
        Route::put('{id}', [UserController::class, 'update'])
            ->middleware('permission:editar-usuario')
            ->name('usuario.update');
    });

    //Rutas para contraseña temporal de usuarios
    Route::post('/admin/users/{user}/reset-password', [UserController::class, 'resetPassword'])
        ->middleware('permission:restablecer-superusuario')
        ->name('admin.users.resetPassword');

    Route::post('/profile/signature', [DigitalSignatureController::class, 'update'])
        ->name('profile.signature.update');

    Route::delete('/profile/signature', [DigitalSignatureController::class, 'destroy'])
        ->name('profile.signature.destroy');

    //DropDown - estas rutas generalmente no necesitan permisos específicos
    Route::prefix('dropdown')->group(function () {
        Route::post('store', [DropDownController::class, 'store'])
            ->middleware('permission:discapacidad-superusuario')
            ->name('dropdown.store');
        Route::post('addDis', [DropDownController::class, 'addDis'])
            ->middleware('permission:distrito-superusuario')
            ->name('dropdown.addDis');
    });
});

// Incluir las rutas de autenticación
require __DIR__ . '/auth.php';
