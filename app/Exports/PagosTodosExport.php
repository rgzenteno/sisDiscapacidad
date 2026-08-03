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
 * Reporte "Total Pagados" (tab general de Bandeja de Pagos), en Excel.
 * Reproduce el mismo modelo que useReportePagosTodosPDF.js: N°, N° Boleta,
 * Nombre, C.I., Distrito, Monto, Usuario Pagador — filas anuladas en
 * rojo/negrita, y un total (monto) al final.
 */
class PagosTodosExport implements FromArray, WithEvents, WithTitle, WithColumnWidths
{
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    protected Collection $datos;
    protected $gestion;
    protected $mes;
    protected ?string $fechaDesde;
    protected ?string $fechaHasta;
    protected ?string $distrito;
    protected bool $verRetro;

    public function __construct(
        $datos,
        $gestion,
        $mes,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        ?string $distrito = null,
        bool $verRetro = false
    ) {
        $this->datos = collect($datos)->map(fn($item) => (object) $item);
        $this->gestion = $gestion;
        $this->mes = $mes;
        $this->fechaDesde = $fechaDesde;
        $this->fechaHasta = $fechaHasta;
        $this->distrito = $distrito;
        $this->verRetro = $verRetro;
    }

    public function title(): string
    {
        return 'Total Pagados';
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 14, 'C' => 30, 'D' => 14, 'E' => 12, 'F' => 14, 'G' => 36];
    }

    public function array(): array
    {
        return [[]];
    }

    private function nombreMes(): string
    {
        $i = (int) $this->mes;
        return self::MESES[$i] ?? '';
    }

    private function montoTotal(): float
    {
        return $this->datos
            ->filter(fn($item) => (float) ($item->pago ?? 1) !== 0.0)
            ->reduce(fn($s, $item) => $s + (float) ($item->monto ?? 0), 0.0);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

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
                    $drawingDerecho->setCoordinates('G1');
                    $drawingDerecho->setOffsetX(30);
                    $drawingDerecho->setOffsetY(3);
                    $drawingDerecho->setWorksheet($sheet);
                }

                // Título institucional de la planilla — reemplaza la línea
                // Gestión/Mes/Total/Monto Total de antes; esos totales ahora
                // van en la fila de TOTAL al final de la tabla (ver abajo).
                $fila = 1;

                $periodoTexto = ($this->fechaDesde && $this->fechaHasta)
                    ? "PERÍODO {$this->fechaDesde} AL {$this->fechaHasta}"
                    : 'MES DE ' . mb_strtoupper($this->nombreMes()) . " {$this->gestion}";

                $tituloLinea1 = 'PLANILLA PAGADOS BONO MENSUAL EN FAVOR DE LAS PERSONAS CON DISCAPACIDAD';
                $tituloLinea2 = "GRAVE MUY GRAVE {$periodoTexto}" . ($this->verRetro ? ' (RETROACTIVOS)' : '');

                foreach ([$tituloLinea1, $tituloLinea2] as $linea) {
                    $sheet->mergeCells("A{$fila}:G{$fila}");
                    $sheet->setCellValue("A{$fila}", $linea);
                    $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(9);
                    $sheet->getStyle("A{$fila}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);
                    $fila++;
                }

                if ($this->distrito) {
                    $sheet->mergeCells("A{$fila}:G{$fila}");
                    $sheet->setCellValue("A{$fila}", 'Distrito: ' . mb_strtoupper($this->distrito));
                    $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(8)->getColor()->setRGB('2563EB');
                    $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                $fila++;

                $filaEncabezado = $fila;
                $headers = ['N°', 'N° Boleta', 'Nombre Completo', 'C.I.', 'Distrito', 'Monto Pagado (Bs)', 'Usuario Pagador'];
                foreach ($headers as $i => $h) {
                    $col = chr(65 + $i);
                    $sheet->setCellValue("{$col}{$filaEncabezado}", $h);
                }
                $sheet->getStyle("A{$filaEncabezado}:G{$filaEncabezado}")->getFont()->setBold(true);
                $sheet->getStyle("A{$filaEncabezado}:G{$filaEncabezado}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
                $sheet->getStyle("A{$filaEncabezado}:G{$filaEncabezado}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getStyle("A{$filaEncabezado}:G{$filaEncabezado}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $fila++;

                $n = 1;
                foreach ($this->datos as $item) {
                    $anulado = (float) ($item->pago ?? 1) === 0.0;
                    $nombre = trim(($item->apellido_persona ?? '') . ' ' . ($item->nombre_persona ?? ''));
                    $pagador = trim((string) ($item->usuario_pagador ?? ''));

                    $sheet->setCellValue("A{$fila}", $n);
                    $sheet->setCellValue("B{$fila}", $item->numero_boleta ?? '');
                    $sheet->setCellValue("C{$fila}", mb_strtoupper($nombre));
                    $sheet->setCellValue("D{$fila}", $item->ci_persona ?? '');
                    $sheet->setCellValue("E{$fila}", $item->distrito ?? '');
                    if ($anulado) {
                        $sheet->setCellValue("F{$fila}", '¡Anulado!');
                    } else {
                        $sheet->setCellValue("F{$fila}", (float) ($item->monto ?? 0));
                        $sheet->getStyle("F{$fila}")->getNumberFormat()->setFormatCode('#,##0');
                    }
                    $sheet->setCellValue("G{$fila}", $pagador);

                    $rango = "A{$fila}:G{$fila}";
                    $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A{$fila}:B{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$fila}:F{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    if ($anulado) {
                        $sheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FECACA');
                        $sheet->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('B91C1C');
                    }

                    $n++;
                    $fila++;
                }

                // Sin fila en blanco antes del total.
                $sheet->mergeCells("A{$fila}:E{$fila}");
                $sheet->setCellValue("A{$fila}", "TOTAL PAGADOS {$periodoTexto}");
                $sheet->setCellValue("F{$fila}", $this->montoTotal());
                $sheet->getStyle("F{$fila}")->getNumberFormat()->setFormatCode('#,##0');
                $rangoTotal = "A{$fila}:G{$fila}";
                $sheet->getStyle($rangoTotal)->getFont()->setBold(true);
                $sheet->getStyle($rangoTotal)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BBF7D0');
                $sheet->getStyle($rangoTotal)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("F{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane('A' . ($filaEncabezado + 1));
            },
        ];
    }
}
