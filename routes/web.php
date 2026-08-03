<?php

use App\Http\Controllers\CarnetController;
use App\Http\Controllers\ConfiguracionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DigitalSignatureController;
use App\Http\Controllers\DropDownController;
use App\Http\Controllers\GestionController;
use App\Http\Controllers\HabilitadoController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PagoSuspendidoController;
use App\Http\Controllers\PresupuestoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\RegistroGeneralController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RetroactivoController;
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
            ->middleware('permission:reporte-gestion')
            ->name('gestion.reporte');
        Route::get('diciembre-prestado/{año}', [GestionController::class, 'getDiciembrePrestado'])
            ->middleware('permission:reporte-gestion')
            ->name('gestion.diciembrePrestado');
        Route::get('mes/{idMes}/personas', [GestionController::class, 'personasPorMes'])
            ->middleware('permission:gestion')
            ->name('gestion.mes.personas');
        Route::post('reporte-mes/excel', [GestionController::class, 'exportReporteMesExcel'])
            ->middleware('permission:reporte-mes')
            ->name('gestion.reporteMes.excel');
        Route::post('reporte-arqueo-general/excel', [GestionController::class, 'exportReporteArqueoGeneralExcel'])
            ->middleware('permission:reporte-gestion')
            ->name('gestion.reporteArqueoGeneral.excel');
        Route::post('store', [GestionController::class, 'store'])
            ->middleware('permission:agregar-gestion')
            ->name('gestion.store');
        Route::post('addMes', [GestionController::class, 'addMes'])
            ->middleware('permission:agregar-mes')
            ->name('gestion.addMes');
        Route::post('mes/preview', [GestionController::class, 'previsualizarMes'])
            ->middleware('permission:agregar-mes')
            ->name('gestion.mes.preview');
        Route::put('{id}', [GestionController::class, 'update'])
            ->middleware('permission:editar-gestion')
            ->name('gestion.update');
        Route::put('mes/{id}', [GestionController::class, 'updateMes'])
            ->middleware('permission:editar-mes')
            ->name('gestion.updateMes');
        Route::get('retroactivo/estado', [RetroactivoController::class, 'estado'])
            ->middleware('permission:agregar-mes')
            ->name('gestion.retroactivo.estado');
        Route::get('retroactivo/detalle', [RetroactivoController::class, 'detalle'])
            ->middleware('permission:agregar-mes')
            ->name('gestion.retroactivo.detalle');
        Route::post('retroactivo/preview', [RetroactivoController::class, 'preview'])
            ->middleware('permission:agregar-mes')
            ->name('gestion.retroactivo.preview');
        Route::post('retroactivo/store', [RetroactivoController::class, 'store'])
            ->middleware('permission:agregar-mes')
            ->name('gestion.retroactivo.store');
        Route::post('retroactivo/toggle', [RetroactivoController::class, 'toggle'])
            ->middleware('permission:superusuario')
            ->name('gestion.retroactivo.toggle');
        Route::post('{id}/cerrar-caja', [GestionController::class, 'cerrarCaja'])
            ->middleware('permission:superusuario')
            ->name('gestion.cerrarCaja');
    });

    // Presupuesto por usuario/cajero y mes (informativo — ver Asignado/Restante
    // en BandejaPagos/index.vue tab Resumen General)
    Route::prefix('presupuesto')->group(function () {
        Route::get('index', [PresupuestoController::class, 'index'])
            ->middleware('permission:presupuesto')
            ->name('presupuesto.index');
        Route::post('store', [PresupuestoController::class, 'store'])
            ->middleware('permission:agregar-presupuesto')
            ->name('presupuesto.store');
        Route::put('{id}', [PresupuestoController::class, 'update'])
            ->middleware('permission:editar-presupuesto')
            ->name('presupuesto.update');
        Route::delete('{id}', [PresupuestoController::class, 'destroy'])
            ->middleware('permission:eliminar-presupuesto')
            ->name('presupuesto.destroy');
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

        Route::post('reporte/excel', [PersonaController::class, 'exportReporteBeneficiarioExcel'])
            ->middleware('permission:reporte-beneficiario')
            ->name('persona.reporte.excel');

        Route::get('showHabilitado/{id}', [HabilitadoController::class, 'showHabilitado'])
            ->middleware('permission:pago')
            ->name('persona.showHabilitado');

        Route::put('estado/{id}', [PersonaController::class, 'updateEstado'])
            ->middleware('permission:agregar-estado')
            ->name('persona.estado.update');

        Route::put('estado/{id}/observacion', [PersonaController::class, 'updateObservacionEstado'])
            ->middleware('permission:agregar-estado')
            ->name('persona.estado.observacion');

        Route::delete('estado/{id}', [PersonaController::class, 'destroyEstado'])
            ->middleware('permission:eliminar-estado')
            ->name('persona.estado.eliminar');

        Route::post('estado/intermedio', [PersonaController::class, 'insertarEstadoIntermedio'])
            ->middleware('permission:agregar-estado')
            ->name('persona.estado.intermedio');

        Route::delete('clear-tutor-session', [PersonaController::class, 'clearTutorSession'])
            ->name('persona.clearTutorSession');
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

    // Configuración de logos institucionales (sacaba.png / sigamos.png),
    // usados en todos los reportes PDF/Excel — solo superusuario.
    Route::prefix('configuracion')->middleware('permission:superusuario')->group(function () {
        Route::get('/', [ConfiguracionController::class, 'index'])
            ->name('configuracion.index');
        Route::post('logo', [ConfiguracionController::class, 'update'])
            ->name('configuracion.logo.update');
        Route::post('parametro/{parametro}', [ConfiguracionController::class, 'actualizarParametro'])
            ->name('configuracion.parametro.update');
    });

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

        /* Route::get('show', [HabilitadoController::class, 'show'])
            ->name('habilitado.show'); */

        Route::get('/habilitados/{id}', [HabilitadoController::class, 'show'])
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
            /* ->middleware('permission:reporteGestion-gestion') */
            ->name('pago.reporteLog');
        Route::post('store', [PagoController::class, 'store'])
            ->middleware('permission:registrar-pago')
            ->name('pago.store');
        Route::post('comp', [PagoController::class, 'comp'])
            ->middleware('permission:registrar-pago')
            ->name('pago.comp');

        Route::patch('pagos/{pago}/anular', [PagoController::class, 'anular'])
            ->middleware('permission:anular-pago')
            ->name('pagos.anular');

        Route::patch('pagos/{pago}/reactivar', [PagoController::class, 'reactivar'])
            ->middleware('permission:superusuario')
            ->name('pagos.reactivar');

        Route::get('bandeja', [PagoController::class, 'bandejaPago'])
            ->name('bandeja.index');
        Route::post('bandeja/no-pagados/excel', [PagoController::class, 'exportNoPagadosExcel'])
            ->name('bandeja.noPagados.excel');
        Route::post('bandeja/bajas/excel', [PagoController::class, 'exportBajasExcel'])
            ->name('bandeja.bajas.excel');
        Route::post('bandeja/individual/excel', [PagoController::class, 'exportPagosIndividualExcel'])
            ->name('bandeja.individual.excel');
        Route::post('bandeja/todos/excel', [PagoController::class, 'exportPagosTodosExcel'])
            ->name('bandeja.todos.excel');
        Route::post('bandeja/resumen/excel', [PagoController::class, 'exportResumenGeneralExcel'])
            ->name('bandeja.resumen.excel');
        Route::post('bandeja/arqueo/excel', [PagoController::class, 'exportArqueoCajaExcel'])
            ->name('bandeja.arqueo.excel');

        Route::get('bandejaReporteLog', [PagoController::class, 'bandejaReporteLog'])
            ->name('bandeja.reporteLog');
        Route::post('descargar-boleta', [PagoController::class, 'descargarBoleta'])
            /* ->middleware('permission:comprobante-pago') */
            ->name('pago.descargarBoleta');

        Route::post('imprimir-boleta', [PagoController::class, 'imprimirBoleta'])
            ->name('pago.imprimirBoleta');

        Route::delete('descargar-boleta/{idPago}', [PagoController::class, 'resetearDescarga'])
            ->middleware('permission:superusuario')
            ->name('pago.resetearDescarga');
    });

    // Suspensión de pagos — decisión interna de UMADIS, independiente de
    // historial_estados (ver Persona/listaHabilitados.vue)
    Route::prefix('pago-suspendido')->group(function () {
        Route::post('store', [PagoSuspendidoController::class, 'store'])
            ->middleware('permission:suspender-pago')
            ->name('pagoSuspendido.store');

        Route::delete('{pagoSuspendido}', [PagoSuspendidoController::class, 'destroy'])
            ->middleware('permission:suspender-pago')
            ->name('pagoSuspendido.destroy');
    });

    // Reporte
    Route::prefix('reporte')->group(function () {
        Route::get('index', [ReporteController::class, 'index'])
            /* ->middleware('permission:ver-reportes') */
            ->name('reportes.index');
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
