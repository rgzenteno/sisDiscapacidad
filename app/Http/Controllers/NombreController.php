<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\NombreIAService;

class NombreController extends Controller
{
    private NombreIAService $nombreService;

    public function __construct(NombreIAService $nombreService)
    {
        $this->nombreService = $nombreService;
    }

    /**
     * Separar un nombre usando IA
     */
    public function separar(Request $request)
    {
        $request->validate([
            'nombre_completo' => 'required|string|max:255'
        ]);

        $nombreCompleto = $request->input('nombre_completo');
        $resultado = $this->nombreService->separarNombre($nombreCompleto);

        return response()->json($resultado);
    }

    /**
     * Separar múltiples nombres
     */
    public function separaBatch(Request $request)
    {
        $request->validate([
            'nombres' => 'required|array',
            'nombres.*' => 'string|max:255'
        ]);

        $nombres = $request->input('nombres');
        $resultados = $this->nombreService->separarBatch($nombres);

        return response()->json([
            'total' => count($resultados),
            'resultados' => $resultados
        ]);
    }

    /**
     * Verificar estado de la API
     */
    public function status()
    {
        $disponible = $this->nombreService->isApiAvailable();

        return response()->json([
            'api_disponible' => $disponible,
            'mensaje' => $disponible 
                ? 'API de IA funcionando correctamente' 
                : 'API de IA no disponible. Usando método de respaldo.'
        ]);
    }
}