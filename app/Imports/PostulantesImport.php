<?php

namespace App\Imports;

use App\Models\Persona;
use App\Services\NombreIAService;
// @phpstan-ignore-next-line
use Maatwebsite\Excel\Concerns\ToModel;
// @phpstan-ignore-next-line
use Maatwebsite\Excel\Concerns\WithHeadingRow;
// @phpstan-ignore-next-line
use Maatwebsite\Excel\Concerns\WithBatchInserts;
// @phpstan-ignore-next-line
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PostulantesImport implements ToModel, WithHeadingRow, WithBatchInserts, WithChunkReading
{
    private NombreIAService $nombreService;
    private array $resultados = [];

    public function __construct()
    {
        $this->nombreService = new NombreIAService();
    }

    public function model(array $row)
    {
        Log::info('Claves del Excel:', array_keys($row));
        // Normalizar claves (eliminar espacios y caracteres especiales)
        $row = array_change_key_case($row, CASE_LOWER);

        // Obtener nombre completo (probando diferentes variaciones)
        $nombreCompleto = $row['apellidos__nombres']
            ?? $row['apellidos_nombres']
            ?? $row['apellidosnombres']
            ?? $row['apellidos']
            ?? '';

        if (empty($nombreCompleto)) {
            Log::warning('Fila sin nombre completo', ['row' => $row]);
            return null;
        }

        // 🤖 Separar con IA
        $separado = $this->nombreService->separarNombre($nombreCompleto);

        $this->resultados[] = [
            'original' => $nombreCompleto,
            'nombre' => $separado['nombre'],
            'apellido' => $separado['apellido'],
            'exito' => $separado['success'] ?? false
        ];

        // Obtener CI (probando variaciones)
        $ci = $row['documento_de_identidad']
            ?? $row['documentodeidentidad']
            ?? $row['documento']
            ?? $row['ci']
            ?? '';

        // Obtener tutor (probando variaciones)
        $tutor = $row['tutora']
            ?? $row['tutor']
            ?? $row['tutora']
            ?? null;

        // Obtener documento respaldo
        $docRespaldo = $row['documento_de_respaldo']
            ?? $row['documentoderespaldo']
            ?? $row['respaldo']
            ?? null;

        return new Persona([
            'ci_persona' => $ci,
            'nombre_persona' => $separado['nombre'],
            'apellido_persona' => $separado['apellido'],
            'tutor_nombre' => $tutor,
            'documento_respaldo' => $docRespaldo,
            'distrito' => null,
            'complemento' => null,
            'fecha_nacimiento' => null,
            'observacion_persona' => null,
            'tipo_registro' => 'postulante',
            'fecha_registro' => Carbon::now()->format('Y-m-d'),
            'id_tutor' => null,
        ]);
    }

    public function batchSize(): int
    {
        return 50;
    }

    public function chunkSize(): int
    {
        return 50;
    }

    public function getResultados(): array
    {
        return $this->resultados;
    }
}