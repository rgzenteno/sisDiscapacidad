<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use App\Models\Mes;
use App\Models\PresupuestoUsuario;
use App\Models\User;
use App\Services\LogService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Presupuesto asignado a cada usuario/cajero por mes+gestión — informativo
 * por ahora (no bloquea el cobro en PagoController::store()), se usa para
 * mostrar "Asignado"/"Restante" en BandejaPagos/index.vue (tab Resumen
 * General). Ver docs del diseño en el plan aprobado con el usuario.
 */
class PresupuestoController extends Controller
{
    public function __construct(protected LogService $logService)
    {
    }

    public function index(Request $request)
    {
        $year = $request->input('gestion_gestion');
        $mesFiltro = $request->input('mes');
        $buscador = $request->input('buscador');

        $gestiones = Gestion::select('gestion as anio')
            ->distinct()
            ->orderBy('gestion', 'desc')
            ->get();

        $mesesNumeros = collect([]);
        $presupuestos = collect([]);
        $montoMes = 0;

        if ($year) {
            $gestion = Gestion::where('gestion', $year)->first();

            if ($gestion) {
                $mesesNumeros = Mes::where('id_gestion', $gestion->id_gestion)
                    ->where('es_retroactivo', false)
                    ->pluck('mes')->sort()->values();

                if ($mesFiltro) {
                    $mes = Mes::where('id_gestion', $gestion->id_gestion)
                        ->where('mes', $mesFiltro)
                        ->where('es_retroactivo', false)
                        ->first();

                    if ($mes) {
                        $montoMes = (float) $mes->monto;

                        $presupuestos = PresupuestoUsuario::query()
                            ->join('users', 'users.id', '=', 'presupuesto_usuario.id_usuario')
                            ->where('presupuesto_usuario.id_mes', $mes->id_mes)
                            ->where('presupuesto_usuario.id_gestion', $gestion->id_gestion)
                            ->select(
                                'presupuesto_usuario.id',
                                'presupuesto_usuario.id_usuario',
                                'presupuesto_usuario.monto_asignado',
                                'users.nombre',
                                'users.apellido',
                                'users.cargo',
                                DB::raw('(SELECT COALESCE(SUM(pg.monto), 0)
                                    FROM pago pg
                                    INNER JOIN habilitado h2 ON h2.id_habilitado = pg.id_habilitado
                                    WHERE pg.id = presupuesto_usuario.id_usuario
                                    AND h2.id_mes = presupuesto_usuario.id_mes
                                    AND pg.pago = 1
                                ) as pagado'),
                                // Para el Arqueo de Caja de este usuario (mismo reporte que
                                // BandejaPagos "Cerrar Caja"/"Resumen General"): cuenta todos
                                // los pagos (incluidos anulados) y los anulados por separado.
                                DB::raw('(SELECT COUNT(*)
                                    FROM pago pg
                                    INNER JOIN habilitado h2 ON h2.id_habilitado = pg.id_habilitado
                                    WHERE pg.id = presupuesto_usuario.id_usuario
                                    AND h2.id_mes = presupuesto_usuario.id_mes
                                ) as cantidad_total_pagos'),
                                DB::raw('(SELECT COUNT(*)
                                    FROM pago pg
                                    INNER JOIN habilitado h2 ON h2.id_habilitado = pg.id_habilitado
                                    WHERE pg.id = presupuesto_usuario.id_usuario
                                    AND h2.id_mes = presupuesto_usuario.id_mes
                                    AND pg.pago = 0
                                ) as cantidad_anulados'),
                            )
                            ->when($buscador, function ($q) use ($buscador) {
                                $q->where(function ($sub) use ($buscador) {
                                    $sub->whereRaw("CONCAT(users.nombre, ' ', users.apellido) LIKE ?", ["%{$buscador}%"])
                                        ->orWhere('presupuesto_usuario.monto_asignado', 'like', "%{$buscador}%");
                                });
                            })
                            ->orderBy('users.nombre')
                            ->paginate(15)
                            ->withQueryString();
                    }
                }
            }
        }

        // Solo usuarios que efectivamente pueden registrar pagos (cajeros),
        // activos (habilitado = 1), sin contar al superUsuario (no es un
        // cajero real) y que todavía no tienen presupuesto asignado este
        // mes+gestión — el único compuesto de presupuesto_usuario permite
        // solo uno por usuario/mes, así que no tiene sentido ofrecerlo de
        // nuevo en el selector de "Asignar Presupuesto".
        $usuariosQuery = User::permission('registrar-pago')
            ->where('habilitado', 1)
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'superUsuario'))
            ->orderBy('nombre');

        if (isset($mes) && $mes) {
            $usuariosQuery->whereNotIn('id', function ($q) use ($mes, $gestion) {
                $q->select('id_usuario')
                    ->from('presupuesto_usuario')
                    ->where('id_mes', $mes->id_mes)
                    ->where('id_gestion', $gestion->id_gestion);
            });
        }

        $usuarios = $usuariosQuery->get(['id', 'nombre', 'apellido']);

        return Inertia::render('Presupuesto/Index', [
            'presupuestos' => $presupuestos,
            'gestiones' => $gestiones,
            'mesesNumeros' => $mesesNumeros,
            'usuarios' => $usuarios,
            'montoMes' => $montoMes,
            'filters' => [
                'gestion' => $year,
                'mes' => $mesFiltro,
                'buscador' => $buscador,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_usuario' => 'required|integer|exists:users,id',
            'gestion' => 'required|integer',
            'mes' => 'required|integer',
            'monto_asignado' => 'required|integer|min:1',
        ]);

        $gestion = Gestion::where('gestion', $request->gestion)->firstOrFail();
        $mes = Mes::where('id_gestion', $gestion->id_gestion)
            ->where('mes', $request->mes)
            ->where('es_retroactivo', false)
            ->firstOrFail();

        $yaExiste = PresupuestoUsuario::where('id_usuario', $request->id_usuario)
            ->where('id_mes', $mes->id_mes)
            ->where('id_gestion', $gestion->id_gestion)
            ->exists();

        if ($yaExiste) {
            return back()->withErrors([
                'error' => 'Ya existe un presupuesto asignado a este usuario para este mes.',
            ]);
        }

        $presupuesto = PresupuestoUsuario::create([
            'id_usuario' => $request->id_usuario,
            'id_mes' => $mes->id_mes,
            'id_gestion' => $gestion->id_gestion,
            'monto_asignado' => $request->monto_asignado,
        ]);

        $usuario = User::find($request->id_usuario);
        $nombreUsuario = trim("{$usuario->nombre} {$usuario->apellido}");

        $this->logService->logCreation(
            'Presupuesto',
            $presupuesto,
            "Se asignó un presupuesto de {$request->monto_asignado} a {$nombreUsuario} para {$request->mes}/{$request->gestion}.",
            null,
            [
                'usuario' => $nombreUsuario,
                'gestión' => $request->gestion,
                'mes' => $request->mes,
                'monto asignado' => $request->monto_asignado,
            ]
        );
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'monto_asignado' => 'required|integer|min:1',
        ]);

        $presupuesto = PresupuestoUsuario::findOrFail($id);
        $montoAnterior = $presupuesto->monto_asignado;

        if ($montoAnterior === (int) $request->monto_asignado) {
            return;
        }

        $presupuesto->update([
            'monto_asignado' => $request->monto_asignado,
        ]);

        $this->logService->logUpdate(
            'Presupuesto',
            $presupuesto,
            [
                'campos_modificados' => ['monto asignado' => $request->monto_asignado],
                'valores_anteriores' => ['monto asignado' => $montoAnterior],
                'valores_nuevos' => ['monto asignado' => $request->monto_asignado],
            ],
            "Se actualizó el presupuesto asignado (ID {$presupuesto->id})."
        );
    }

    public function destroy(string $id)
    {
        $presupuesto = PresupuestoUsuario::findOrFail($id);

        $this->logService->logDeletion(
            'Presupuesto',
            $presupuesto,
            "Se eliminó el presupuesto asignado (ID {$presupuesto->id}).",
            [
                'id_usuario' => $presupuesto->id_usuario,
                'monto asignado' => $presupuesto->monto_asignado,
            ]
        );

        $presupuesto->delete();
    }
}
