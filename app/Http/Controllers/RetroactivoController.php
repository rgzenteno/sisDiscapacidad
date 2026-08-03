<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use App\Services\LogService;
use App\Services\RetroactivoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class RetroactivoController extends Controller
{
    protected $retroactivoService;
    protected $logService;

    public function __construct(RetroactivoService $retroactivoService, LogService $logService)
    {
        $this->retroactivoService = $retroactivoService;
        $this->logService = $logService;
    }

    /**
     * Estado del proceso de retroactivos para una gestión: si ya está
     * habilitado, cuál es la siguiente posición a cargar, y los meses-retro
     * ya creados.
     */
    public function estado(Request $request)
    {
        $request->validate([
            'id_gestion' => 'required|integer|exists:gestion,id_gestion',
        ]);

        $estado = $this->retroactivoService->estado((int) $request->input('id_gestion'));

        return response()->json($estado);
    }

    /**
     * Resumen de un mes-retro ya creado (habilitados, pagados, no pagados,
     * presupuesto usado), para el modal de detalle.
     */
    public function detalle(Request $request)
    {
        $request->validate([
            'id_mes' => 'required|integer|exists:mes,id_mes',
        ]);

        try {
            $detalle = $this->retroactivoService->detalleMesRetro((int) $request->input('id_mes'));

            return response()->json($detalle);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Activa/desactiva el apartado de carga de retroactivos para una gestión.
     * Switch único por gestión (no por mes), reservado a superusuario.
     */
    public function toggle(Request $request)
    {
        $request->validate([
            'id_gestion' => 'required|integer|exists:gestion,id_gestion',
        ]);

        $idGestion = (int) $request->input('id_gestion');
        $gestion = Gestion::find($idGestion);

        if ($gestion && $gestion->estaCerrada()) {
            return response()->json(['error' => 'La caja de esta gestión ya está cerrada.'], 422);
        }

        $habilitado = $this->retroactivoService->toggleHabilitado($idGestion);

        $user = Auth::user();

        $this->logService->log(
            $habilitado ? 'retroactivos habilitados' : 'retroactivos deshabilitados',
            'Gestion',
            "{$user->nombre} {$user->apellido} " . ($habilitado ? 'habilitó' : 'deshabilitó')
                . " el apartado de retroactivos para la gestión {$gestion->gestion}.",
            $gestion,
            ['retroactivos_habilitado' => $habilitado]
        );

        return response()->json(['retroactivos_habilitado' => $habilitado]);
    }

    /**
     * Previsualiza el presupuesto sugerido para el mes-retro de la siguiente
     * posición disponible, a partir de los registros (ci + nombre) extraídos
     * del archivo Excel cargado. No crea nada todavía.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'id_gestion' => 'required|integer|exists:gestion,id_gestion',
            // Solo enero (2) a octubre (11) del ciclo SIGEP — diciembre (1) y
            // noviembre (12) quedan fuera del alcance de la carga de retro.
            'posicion' => 'required|integer|min:2|max:11',
            'registros' => 'required|array|min:1',
            'registros.*.ci' => 'required|string',
            'registros.*.nombre' => 'nullable|string',
        ]);

        try {
            $resultado = $this->retroactivoService->calcularPresupuestoSugerido(
                (int) $request->input('id_gestion'),
                (int) $request->input('posicion'),
                $request->input('registros')
            );

            return response()->json($resultado);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Confirma la carga del mes-retro de la siguiente posición disponible:
     * crea el mes-retro + boleta_consecutivo, y procesa cada registro
     * (crea persona nueva si el CI no existe, historial_estados inicial, y
     * el habilitado correspondiente si el estado vigente en ese mes histórico
     * lo permite).
     */
    public function store(Request $request)
    {
        $sinRetroactivo = $request->boolean('sin_retroactivo');

        $request->validate([
            'id_gestion' => 'required|integer|exists:gestion,id_gestion',
            // Solo enero (2) a octubre (11) del ciclo SIGEP — diciembre (1) y
            // noviembre (12) quedan fuera del alcance de la carga de retro.
            'posicion' => 'required|integer|min:2|max:11',
            'presupuesto' => 'required|integer|min:0',
            'sin_retroactivo' => 'nullable|boolean',
            // Cuando el mes no tuvo retroactivo no hay archivo que subir, así
            // que no tiene sentido exigir registros — ese camino lo maneja
            // confirmarSinRetroactivo() más abajo, sin tocar esta validación.
            'registros' => $sinRetroactivo ? 'nullable|array' : 'required|array|min:1',
            'registros.*.ci' => 'required_with:registros|string',
            'registros.*.nombre' => 'nullable|string',
            // Personas marcadas en el preview para excluir del retro a mano
            // (ej. antecedente de baja que el historial ya no refleja como
            // vigente) — no tocan historial_estados, solo evitan que se les
            // cree el habilitado-retro.
            'exclusiones' => 'nullable|array',
            'exclusiones.*.ci' => 'required|string',
            'exclusiones.*.motivo' => 'required|string',
        ]);

        $gestion = Gestion::find($request->input('id_gestion'));

        if ($gestion && $gestion->estaCerrada()) {
            return redirect()->back()->with('error', 'La caja de esta gestión ya está cerrada; no se pueden cargar retroactivos.');
        }

        $user = Auth::user();

        try {
            if ($sinRetroactivo) {
                $resultado = $this->retroactivoService->confirmarSinRetroactivo(
                    (int) $request->input('id_gestion'),
                    (int) $request->input('posicion')
                );
            } else {
                $exclusionesManuales = collect($request->input('exclusiones', []))
                    ->pluck('motivo', 'ci')
                    ->all();

                $resultado = $this->retroactivoService->confirmarCargaRetro(
                    (int) $request->input('id_gestion'),
                    (int) $request->input('posicion'),
                    (int) $request->input('presupuesto'),
                    $request->input('registros'),
                    $user->id,
                    "{$user->nombre} {$user->apellido}",
                    $exclusionesManuales
                );
            }
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $mesRetro = $resultado['mes_retro'];

        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
        $nombreMesOriginal = $meses[$mesRetro->mes_original] ?? $mesRetro->mes_original;

        if ($sinRetroactivo) {
            $this->logService->logCreation(
                'Gestion',
                $mesRetro,
                "{$user->nombre} {$user->apellido} confirmó que {$nombreMesOriginal} no tuvo retroactivo — no se registró ningún pago.",
                null,
                ['mes_original' => $nombreMesOriginal]
            );
        } else {
            $this->logService->logCreation(
                'Gestion',
                $mesRetro,
                "Se registró el mes retroactivo de {$nombreMesOriginal} en el sistema.",
                null,
                [
                    'mes_original' => $nombreMesOriginal,
                    'monto Bs.' => $mesRetro->monto,
                    'presupuesto Bs.' => $mesRetro->presupuesto,
                    'personas_nuevas' => $resultado['personas_nuevas'],
                    'habilitados_creados' => $resultado['habilitados_creados'],
                    'excluidos' => count($resultado['excluidos']),
                    'total_procesados' => $resultado['total_procesados'],
                ]
            );

            $this->logService->logImport(
                'Gestion',
                [
                    'total' => $resultado['total_procesados'],
                    'successful' => $resultado['habilitados_creados'],
                    'failed' => count($resultado['excluidos']),
                    'filename' => "Retroactivo de {$nombreMesOriginal}",
                    'errors' => array_map(
                        fn($e) => "CI: {$e['ci']} - excluido ({$e['motivo']})",
                        $resultado['excluidos']
                    ),
                ],
                "Carga de retroactivo de {$nombreMesOriginal}: {$resultado['personas_nuevas']} personas nuevas, "
                    . "{$resultado['habilitados_creados']} habilitados creados, "
                    . count($resultado['excluidos']) . ' excluidos por baja/depurado.'
            );
        }

        return redirect()->back()->with('mesRetroCreado', [
            'id_mes' => $mesRetro->id_mes,
            'mes' => $mesRetro->mes,
            'mes_original' => $mesRetro->mes_original,
            'monto' => $mesRetro->monto,
            'presupuesto' => $mesRetro->presupuesto,
            'personas_nuevas' => $resultado['personas_nuevas'],
            'habilitados_creados' => $resultado['habilitados_creados'],
            'excluidos' => $resultado['excluidos'],
        ]);
    }
}
