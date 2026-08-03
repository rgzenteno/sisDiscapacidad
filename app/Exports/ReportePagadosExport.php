<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Reporte de pagados/no pagados de un mes, en Excel. Igual que el PDF:
 * la primera hoja es el mes normal, y si ese mes ya tiene su retroactivo
 * cargado, se agrega una segunda hoja separada con esa planilla.
 */
class ReportePagadosExport implements WithMultipleSheets
{
    protected $datosNormal;
    protected $gestion;
    protected $mes;
    protected float $montoNormal;
    protected $datosRetro;
    protected float $montoRetro;

    public function __construct(
        $datosNormal,
        $gestion,
        $mes,
        float $montoNormal,
        $datosRetro = null,
        float $montoRetro = 0
    ) {
        $this->datosNormal = $datosNormal;
        $this->gestion = $gestion;
        $this->mes = $mes;
        $this->montoNormal = $montoNormal;
        $this->datosRetro = $datosRetro;
        $this->montoRetro = $montoRetro;
    }

    public function sheets(): array
    {
        $sheets = [
            new ReportePagadosSheet($this->datosNormal, $this->gestion, $this->mes, $this->montoNormal, false),
        ];

        if ($this->datosRetro !== null) {
            $sheets[] = new ReportePagadosSheet($this->datosRetro, $this->gestion, $this->mes, $this->montoRetro, true);
        }

        return $sheets;
    }
}
