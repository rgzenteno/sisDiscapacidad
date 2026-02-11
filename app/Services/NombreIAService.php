<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NombreIAService
{
    private string $apiUrl;

    public function __construct()
    {
        // URL de tu API de Python
        $this->apiUrl = 'http://localhost:5000';
    }

    /**
     * Separar un nombre completo usando IA
     * 
     * @param string $nombreCompleto
     * @return array ['nombre' => string, 'apellido' => string]
     */
    public function separarNombre(string $nombreCompleto): array
    {
        try {
            $response = Http::timeout(10)->post("{$this->apiUrl}/separar", [
                'nombre_completo' => $nombreCompleto
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return [
                    'nombre' => $data['nombre'] ?? '',
                    'apellido' => $data['apellido'] ?? '',
                    'original' => $data['original'] ?? $nombreCompleto,
                    'success' => true
                ];
            }

            // Si falla la API, usar fallback
            return $this->fallbackSeparacion($nombreCompleto);

        } catch (\Exception $e) {
            Log::error('Error en NombreIAService: ' . $e->getMessage());
            
            // Si hay error, usar método de respaldo
            return $this->fallbackSeparacion($nombreCompleto);
        }
    }

    /**
     * Separar múltiples nombres de una vez
     * 
     * @param array $nombres
     * @return array
     */
    public function separarBatch(array $nombres): array
    {
        try {
            $response = Http::timeout(30)->post("{$this->apiUrl}/batch", [
                'nombres' => $nombres
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['resultados'] ?? [];
            }

            // Si falla, procesar uno por uno con fallback
            return array_map(fn($nombre) => $this->fallbackSeparacion($nombre), $nombres);

        } catch (\Exception $e) {
            Log::error('Error en separarBatch: ' . $e->getMessage());
            
            // Si falla, usar fallback para cada nombre
            return array_map(fn($nombre) => $this->fallbackSeparacion($nombre), $nombres);
        }
    }

    /**
     * Verificar si la API está disponible
     * 
     * @return bool
     */
    public function isApiAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->apiUrl}/health");
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Método de respaldo si la IA no está disponible
     * Usa separación simple por espacios
     * 
     * @param string $nombreCompleto
     * @return array
     */
    private function fallbackSeparacion(string $nombreCompleto): array
    {
        $nombreCompleto = trim($nombreCompleto);
        $partes = explode(' ', $nombreCompleto);
        
        if (count($partes) === 1) {
            return [
                'nombre' => $partes[0],
                'apellido' => '',
                'original' => $nombreCompleto,
                'success' => false,
                'fallback' => true
            ];
        }
        
        // Primera palabra = nombre, resto = apellido
        $nombre = $partes[0];
        $apellido = implode(' ', array_slice($partes, 1));
        
        return [
            'nombre' => $nombre,
            'apellido' => $apellido,
            'original' => $nombreCompleto,
            'success' => false,
            'fallback' => true
        ];
    }
}