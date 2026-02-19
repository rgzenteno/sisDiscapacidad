<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReporteController extends Controller
{
    /**
     * Vista principal que renderiza el componente
     */
    public function index()
    {
        $gestiones = Gestion::pluck('gestion');

        return Inertia::render('ReportesDatos/index', [
            'resultados' => [],
            'gestiones' => $gestiones
        ]);
    }

    public function buscar(Request $request)
    {
        // Consulta base (usando alias para evitar duplicados)
        $query = Persona::query()
            ->leftJoin('habilitado', 'persona.id_persona', '=', 'habilitado.id_persona')
            ->leftJoin('pago', 'habilitado.id_habilitado', '=', 'pago.id_habilitado')
            ->leftJoin('carnet', 'persona.id_persona', '=', 'carnet.id_persona')
            ->leftJoin('tutor', 'persona.id_tutor', '=', 'tutor.id_tutor')
            ->leftJoin('gestion', 'habilitado.id_gestion', '=', 'gestion.id_gestion')
            ->select(
                'persona.id_persona',
                'persona.nombre_persona',
                'persona.apellido_persona',
                'persona.ci_persona',
                'persona.distrito',
                'persona.estado',
                'tutor.nombre_tutor',
                'tutor.apellido_tutor',
                'tutor.telefono',
                'tutor.direccion',
                'carnet.discapacidad',
                'gestion.gestion',
                'gestion.monto',
                'habilitado.fecha_habilitado',
                'habilitado.observaciones_habilitado',
                DB::raw('CASE WHEN pago.id_pago IS NOT NULL THEN pago.fecha_pago ELSE NULL END as fecha_pago')
            );

        // Filtros básicos (sin cambios)
        if ($request->filled('distrito')) {
            $query->where('persona.distrito', $request->distrito);
        }

        if ($request->filled('gestion')) {
            $query->where('gestion.gestion', $request->gestion);
        }

        if ($request->filled('carnet')) {
            if ($request->carnet === '1') {
                $query->whereNotNull('carnet.id_carnet');
            } elseif ($request->carnet === '0') {
                $query->whereNull('carnet.id_carnet');
            }
        }

        if ($request->filled('pagados')) {
            if ($request->pagados === '0') {
                $query->whereNull('pago.pago');
            } else {
                $query->where('pago.pago', $request->pagados);
            }
        }

        // Filtros especiales (corregidos con alias único en subconsulta)
        if ($request->filled('discapacidad') || $request->filled('estado')) {
            $subQuery = Persona::query()
                ->from('persona as persona_subq')  // ¡Alias único aquí!
                ->leftJoin('habilitado', 'persona_subq.id_persona', '=', 'habilitado.id_persona')
                ->leftJoin('carnet', 'persona_subq.id_persona', '=', 'carnet.id_persona')
                ->select('persona_subq.id_persona')
                ->distinct();

            if ($request->filled('discapacidad')) {
                $subQuery->where('carnet.discapacidad', $request->discapacidad);
            }

            if ($request->filled('estado')) {
                $estados = [
                    'Activo' => 'activo',
                    'Baja Temporal' => 'bajatemporal',
                    'Baja Definitiva' => 'bajadefinitiva',
                ];
                $estadoNormalizado = $estados[strtolower(trim($request->estado))] ?? 'bajadefinitiva';
                $subQuery->where('persona_subq.estado', $estadoNormalizado);
            }

            $query->whereIn('persona.id_persona', $subQuery);
        } else {
            $query->groupBy([
                'persona.id_persona',
                'persona.nombre_persona',
                'persona.apellido_persona',
                'persona.ci_persona',
                'persona.distrito',
                'persona.estado',
                'tutor.nombre_tutor',
                'tutor.apellido_tutor',
                'tutor.telefono',
                'tutor.direccion',
                'carnet.discapacidad',
                'gestion.gestion',
                'gestion.monto',
                'habilitado.fecha_habilitado',
                'habilitado.observaciones_habilitado',
                'pago.id_pago',
                'pago.fecha_pago'
            ]);
        }

        // Paginación y resultados (sin cambios)
        $resultados = $query->orderBy('persona.id_persona', 'desc')->paginate(15);
        $resultadosReporte = clone $query;

        return Inertia::render('ReportesDatos/index', [
            'resultados' => $resultados,
            'resultadosReporte' => $resultadosReporte->get(),
            'gestiones' => Gestion::pluck('gestion')
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
