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
 * Reporte "Resumen General" (tab resumen de Bandeja de Pagos, solo admin),
 * en Excel. Reproduce el mismo modelo que useReporteResumenPDF.js: N°,
 * Nombre del Usuario, Cantidad Beneficiarios, Monto Unitario, Total Monto,
 * con una fila TOTAL al final.
 */
class ResumenGeneralExport implements FromArray, WithEvents, WithTitle, WithColumnWidths
{
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    protected Collection $datos;
    protected $gestion;
    protected $mes;
    protected string $nombreUsuario;
    protected ?string $fechaDesde;
    protected ?string $fechaHasta;

    public function __construct(
        $datos,
        $gestion,
        $mes,
        string $nombreUsuario,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ) {
        $this->datos = collect($datos)->map(fn($item) => (object) $item);
        $this->gestion = $gestion;
        $this->mes = $mes;
        $this->nombreUsuario = $nombreUsuario;
        $this->fechaDesde = $fechaDesde;
        $this->fechaHasta = $fechaHasta;
    }

    public function title(): string
    {
        return 'Resumen General';
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 34, 'C' => 22, 'D' => 18, 'E' => 18];
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

    /**
     * Primer y último día del mes, mismo cálculo que rangoMes() en
     * useReporteResumenPDF.js.
     */
    private function rangoMes(): array
    {
        $mes = str_pad((string) (int) $this->mes, 2, '0', STR_PAD_LEFT);
        $gestion = (string) $this->gestion;
        $ultimoDia = (int) date('t', mktime(0, 0, 0, (int) $this->mes, 1, (int) $this->gestion));
        return ["01/{$mes}/{$gestion}", "{$ultimoDia}/{$mes}/{$gestion}"];
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
                    $drawingDerecho->setCoordinates('D1');
                    $drawingDerecho->setOffsetX(50);
                    $drawingDerecho->setOffsetY(3);
                    $drawingDerecho->setWorksheet($sheet);
                }

                // Mismo bloque informativo que drawInfoBlock() en el PDF de
                // referencia (useReporteResumenPDF.js): NO hay un título grande
                // tipo "REPORTE RESUMEN GENERAL DE PAGOS" en el PDF — solo estas
                // 3 líneas. El Excel anterior inventaba un título/periodo/usuario
                // distintos a lo que el PDF realmente muestra; se alinea acá.
                if ($this->fechaDesde && $this->fechaHasta) {
                    [$arqueoDesde, $arqueoHasta] = [$this->fechaDesde, $this->fechaHasta];
                } elseif ($this->gestion && $this->mes) {
                    [$arqueoDesde, $arqueoHasta] = $this->rangoMes();
                } else {
                    [$arqueoDesde, $arqueoHasta] = ['--', '--'];
                }

                $fila = 1;

                $sheet->mergeCells("A{$fila}:E{$fila}");
                $sheet->setCellValue("A{$fila}", 'Fecha y hora del Reporte: ' . now()->format('d/m/Y H:i'));
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(9);
                $fila++;

                $sheet->mergeCells("A{$fila}:E{$fila}");
                $sheet->setCellValue("A{$fila}", "Arqueo de caja: {$arqueoDesde} al {$arqueoHasta}");
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(9);
                $fila++;

                $sheet->mergeCells("A{$fila}:C{$fila}");
                $sheet->setCellValue("A{$fila}", 'Usuario: ' . mb_strtoupper($this->nombreUsuario));
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(9);

                $sheet->mergeCells("D{$fila}:E{$fila}");
                $sheet->setCellValue("D{$fila}", 'Mes de Pago: ' . $this->nombreMes());
                $sheet->getStyle("D{$fila}")->getFont()->setBold(true)->setSize(9);
                $fila += 2;

                $filaEncabezado = $fila;
                $headers = ['N°', 'Nombre Completo del Usuario', 'Cantidad Beneficiarios', 'Monto Unitario (Bs)', 'Total Monto (Bs)'];
                foreach ($headers as $i => $h) {
                    $col = chr(65 + $i);
                    $sheet->setCellValue("{$col}{$filaEncabezado}", $h);
                }
                $sheet->getStyle("A{$filaEncabezado}:E{$filaEncabezado}")->getFont()->setBold(true);
                $sheet->getStyle("A{$filaEncabezado}:E{$filaEncabezado}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
                $sheet->getStyle("A{$filaEncabezado}:E{$filaEncabezado}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getStyle("A{$filaEncabezado}:E{$filaEncabezado}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $fila++;

                $n = 1;
                $totalCantidad = 0;
                $totalMonto = 0.0;
                $montoUnitario = $this->datos->first()->monto ?? 0;

                foreach ($this->datos as $item) {
                    $nombre = trim(($item->nombre ?? '') . ' ' . ($item->apellido ?? ''));
                    $cantidad = (int) ($item->cantidad_pagos ?? 0);
                    $unitario = (float) ($item->monto ?? 0);
                    $total = (float) ($item->monto_total ?? 0);

                    $sheet->setCellValue("A{$fila}", $n);
                    $sheet->setCellValue("B{$fila}", mb_strtoupper($nombre));
                    $sheet->setCellValue("C{$fila}", $cantidad);
                    $sheet->setCellValueExplicit("D{$fila}", number_format($unitario, 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValueExplicit("E{$fila}", number_format($total, 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                    $rango = "A{$fila}:E{$fila}";
                    $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$fila}:E{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $totalCantidad += $cantidad;
                    $totalMonto += $total;

                    $n++;
                    $fila++;
                }

                $sheet->mergeCells("A{$fila}:B{$fila}");
                $sheet->setCellValue("A{$fila}", 'TOTAL');
                $sheet->setCellValue("C{$fila}", $totalCantidad);
                $sheet->setCellValueExplicit("D{$fila}", number_format($montoUnitario, 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("E{$fila}", number_format($totalMonto, 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                $rangoTotal = "A{$fila}:E{$fila}";
                $sheet->getStyle($rangoTotal)->getFont()->setBold(true);
                $sheet->getStyle($rangoTotal)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BBF7D0');
                $sheet->getStyle($rangoTotal)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("C{$fila}:E{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane('A' . ($filaEncabezado + 1));
            },
        ];
    }
}
