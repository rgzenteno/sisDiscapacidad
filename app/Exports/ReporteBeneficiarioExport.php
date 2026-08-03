<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Reporte de Informe Beneficiarios (Persona/reporteBeneficiario.vue), en Excel
 * — mismo modelo que useReporteBeneficiarioPDF.js: N°, C.I., Apellidos y
 * nombres, Grado de discapacidad, Monto a Pagar, Observaciones, con las
 * mismas filas resaltadas para bajas y el mismo bloque de totales al final.
 */
class ReporteBeneficiarioExport implements FromArray, WithEvents, WithTitle, WithColumnWidths
{
    private const MESES = [
        1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
        5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
        9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
    ];

    protected Collection $datos;
    protected $gestion;
    protected $mes;
    protected bool $esRetro;

    public function __construct($datos, $gestion, $mes, bool $esRetro = false)
    {
        $this->datos = collect($datos)->map(fn($item) => (object) $item);
        $this->gestion = $gestion;
        $this->mes = $mes;
        $this->esRetro = $esRetro;
    }

    public function title(): string
    {
        return 'Informe Beneficiarios';
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 14, 'C' => 40, 'D' => 26, 'E' => 16, 'F' => 34];
    }

    public function array(): array
    {
        return [[]];
    }

    private function nombreMes(): string
    {
        return self::MESES[(int) $this->mes] ?? '';
    }

    private function montoUnitario(): float
    {
        return (float) ($this->datos->first()->monto ?? 0);
    }

    /**
     * "activo" y "pagos_suspendidos" cuentan igual (a pagar) — mismo criterio
     * que la tabla y el PDF, así el total general siempre cuadra con la suma
     * del desglose.
     */
    private function totales(): array
    {
        $monto = $this->montoUnitario();

        $activos = $this->datos->filter(fn($i) => in_array($i->estado_periodo ?? null, ['activo', 'pagos_suspendidos'], true));
        $bajaDefinitiva = $this->datos->filter(fn($i) => ($i->estado_periodo ?? null) === 'baja_definitiva');
        $bajaTemporal = $this->datos->filter(fn($i) => ($i->estado_periodo ?? null) === 'baja_temporal');

        return [
            'monto_unitario' => $monto,
            'monto_total' => $this->datos->count() * $monto,
            'cantidad_baja_definitiva' => $bajaDefinitiva->count(),
            'monto_baja_definitiva' => $bajaDefinitiva->count() * $monto,
            'cantidad_baja_temporal' => $bajaTemporal->count(),
            'monto_baja_temporal' => $bajaTemporal->count() * $monto,
            'cantidad_activos' => $activos->count(),
            'monto_activos' => $activos->count() * $monto,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $totales = $this->totales();
                $mesNombre = $this->nombreMes();

                $logoPath = public_path('images/sacaba.png');
                if (file_exists($logoPath)) {
                    $sheet->getRowDimension(1)->setRowHeight(20);
                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(40);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(4);
                    $drawing->setOffsetY(3);
                    $drawing->setWorksheet($sheet);
                }

                $logoDerechoPath = public_path('images/sigamos.png');
                if (file_exists($logoDerechoPath)) {
                    $drawingDerecho = new Drawing();
                    $drawingDerecho->setName('Logo Sigamos');
                    $drawingDerecho->setPath($logoDerechoPath);
                    $drawingDerecho->setHeight(30);
                    $drawingDerecho->setCoordinates('E1');
                    $drawingDerecho->setOffsetX(60);
                    $drawingDerecho->setOffsetY(3);
                    $drawingDerecho->setWorksheet($sheet);
                }

                $fila = 1;

                $sheet->mergeCells("A{$fila}:F{$fila}");
                $sheet->setCellValue("A{$fila}", 'PLANILLA DE PAGO DE BONO MENSUAL A FAVOR DE LAS PERSONAS CON DISCAPACIDAD');
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fila++;

                $tituloRetro = $this->esRetro ? ' (RETROACTIVO)' : '';
                $sheet->mergeCells("A{$fila}:F{$fila}");
                $sheet->setCellValue("A{$fila}", "GRAVE Y MUY GRAVE MES DE {$mesNombre} GESTIÓN {$this->gestion}{$tituloRetro}");
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fila += 2;

                $filaEncabezado = $fila;
                $headers = ['N°', 'C.I.', 'Apellidos y nombres P.C.D.', 'Grado de discapacidad', 'Monto a Pagar (Bs.)', 'Observaciones'];
                foreach ($headers as $i => $h) {
                    $col = chr(65 + $i);
                    $sheet->setCellValue("{$col}{$filaEncabezado}", $h);
                }
                $sheet->getStyle("A{$filaEncabezado}:F{$filaEncabezado}")->getFont()->setBold(true);
                $sheet->getStyle("A{$filaEncabezado}:F{$filaEncabezado}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
                $sheet->getStyle("A{$filaEncabezado}:F{$filaEncabezado}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getStyle("A{$filaEncabezado}:F{$filaEncabezado}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $fila++;

                $n = 1;
                foreach ($this->datos as $item) {
                    $estado = $item->estado_periodo ?? null;
                    $nombre = trim(($item->apellido ?? '') . ' ' . ($item->nombre ?? '')) ?: ($item->nombre_completo ?? '');
                    $obs = $estado !== 'activo' ? mb_strtoupper((string) ($item->observaciones ?? '')) : '';

                    $sheet->setCellValue("A{$fila}", $n);
                    $sheet->setCellValue("B{$fila}", $item->ci ?? '');
                    $sheet->setCellValue("C{$fila}", mb_strtoupper($nombre));
                    $sheet->setCellValue("D{$fila}", 'GRAVE MUY GRAVE');
                    $sheet->setCellValue("E{$fila}", (float) ($item->monto ?? 0));
                    $sheet->getStyle("E{$fila}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->setCellValue("F{$fila}", $obs);

                    $rango = "A{$fila}:F{$fila}";
                    $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A{$fila}:B{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$fila}:F{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    if ($estado === 'baja_temporal') {
                        $sheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('002060');
                        $sheet->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('00B0F0');
                    } elseif ($estado === 'baja_definitiva') {
                        $sheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
                        $sheet->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('FF0000');
                    }

                    $n++;
                    $fila++;
                }

                // Sin fila en blanco entre la última fila de datos y los totales
                // (bug reportado: el Excel tenía un salto que el PDF no tiene).
                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}", 'TOTAL PLANILLA GENERAL');
                $sheet->setCellValue("E{$fila}", $totales['monto_total']);
                $sheet->getStyle("E{$fila}")->getNumberFormat()->setFormatCode('#,##0');
                $fila++;

                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}", "{$totales['cantidad_baja_definitiva']} PERSONAS CON DISCAPACIDAD GRAVE Y MUY GRAVE FALLECIERON HASTA LA FECHA NO COBRARAN DEL MES DE {$mesNombre} DEL AÑO {$this->gestion}");
                $sheet->setCellValue("E{$fila}", $totales['monto_baja_definitiva']);
                $sheet->getStyle("E{$fila}")->getNumberFormat()->setFormatCode('#,##0');
                $fila++;

                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}", "{$totales['cantidad_baja_temporal']} FUNCIONARIOS PÚBLICOS CON ITEM (MADRE O PADRE O TUTOR) LOS CUALES NO ACCEDEN AL BONO DEL MES DE {$mesNombre} DEL AÑO {$this->gestion}");
                $sheet->setCellValue("E{$fila}", $totales['monto_baja_temporal']);
                $sheet->getStyle("E{$fila}")->getNumberFormat()->setFormatCode('#,##0');
                $fila++;

                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}", "TOTAL PERSONAS CON DISCAPACIDAD A PAGAR MES DE {$mesNombre} {$this->gestion} ({$totales['cantidad_activos']} PERSONAS)");
                $sheet->setCellValue("E{$fila}", $totales['monto_activos']);
                $sheet->getStyle("E{$fila}")->getNumberFormat()->setFormatCode('#,##0');

                $filaTotalesDesde = $fila - 3;
                $rangoTotales = "A{$filaTotalesDesde}:F{$fila}";
                $sheet->getStyle($rangoTotales)->getFont()->setBold(true);
                $sheet->getStyle($rangoTotales)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A{$filaTotalesDesde}:D{$fila}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("E{$filaTotalesDesde}:E{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A{$fila}:F{$fila}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('87CEEB');

                $sheet->freezePane('A' . ($filaEncabezado + 1));
            },
        ];
    }
}
