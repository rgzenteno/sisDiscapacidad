<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/**
 * Arqueo general de tesorería de una gestión, en Excel. Reproduce la misma
 * tabla y totales que useReporteArqueoGeneralPDF.js (el PDF): 12 filas
 * (diciembre primero, luego enero-noviembre), con presupuesto/pagado/saldo
 * calculados como monto × cantidad correspondiente, y un total general.
 */
class ReporteArqueoGeneralExport implements FromArray, WithEvents, WithTitle, WithColumnWidths
{
    private const ORDEN_MESES = [12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

    private const MESES = [
        1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
        5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
        9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
    ];

    protected Collection $datos;
    protected $gestion;
    protected string $nombreUsuario;

    public function __construct($datos, $gestion, string $nombreUsuario)
    {
        $this->datos = collect($datos)->map(fn($item) => (object) $item);
        $this->gestion = $gestion;
        $this->nombreUsuario = $nombreUsuario;
    }

    public function title(): string
    {
        return 'Arqueo General';
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 34, 'C' => 20, 'D' => 16, 'E' => 20, 'F' => 16, 'G' => 18, 'H' => 20];
    }

    // El contenido se escribe en el evento AfterSheet (encabezado, colores,
    // fila de totales) — array() solo satisface la interfaz que exige el paquete.
    public function array(): array
    {
        return [[]];
    }

    private function nombreMes(int $mes): string
    {
        return self::MESES[$mes] ?? 'MES';
    }

    /**
     * Arma las 12 filas del ciclo (diciembre primero), rellenando con ceros
     * los meses que todavía no existen en $this->datos — mismo criterio que
     * arr12 en useReporteArqueoGeneralPDF.js, para que el Excel muestre
     * exactamente el mismo modelo que el PDF.
     */
    private function filas12Meses(): array
    {
        $porMes = $this->datos->keyBy(fn($item) => (int) ($item->mes ?? 0));

        return collect(self::ORDEN_MESES)->map(function ($numMes) use ($porMes) {
            $item = $porMes->get($numMes);

            return (object) [
                'mes' => $numMes,
                // Diciembre "prestado" pertenece a la gestión ANTERIOR — cada
                // fila trae su propio año; solo si el mes falta por completo
                // (relleno en cero) se usa la gestión seleccionada.
                'gestion' => $item->gestion ?? $this->gestion,
                'presupuesto_mes' => (float) ($item->presupuesto_mes ?? 0),
                'cantidad_habilitadas' => (int) ($item->cantidad_habilitadas ?? 0),
                'cantidad_total_pagos' => (int) ($item->cantidad_total_pagos ?? 0),
                'cantidad_no_pagados' => (int) ($item->cantidad_no_pagados ?? 0),
            ];
        })->all();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $filas = $this->filas12Meses();

                $fila = 1;

                // Logo (mismo archivo que usa el PDF) — flota sobre la
                // esquina superior izquierda; el título/subtítulo siguen
                // centrados en las mismas filas, igual que en el PDF, así
                // que no se superponen.
                $logoPath = public_path('images/sacaba.png');
                if (file_exists($logoPath)) {
                    $sheet->getRowDimension(1)->setRowHeight(20);
                    $sheet->getRowDimension(2)->setRowHeight(18);
                    $sheet->getRowDimension(3)->setRowHeight(16);

                    $drawing = new Drawing();
                    $drawing->setName('Logo');
                    $drawing->setDescription('Gobierno Autónomo Municipal');
                    $drawing->setPath($logoPath);
                    $drawing->setHeight(58);
                    $drawing->setCoordinates('A1');
                    $drawing->setOffsetX(4);
                    $drawing->setOffsetY(3);
                    $drawing->setWorksheet($sheet);
                }

                $sheet->mergeCells("A{$fila}:H{$fila}");
                $sheet->setCellValue("A{$fila}", 'ARQUEO GENERAL DE TESORERÍA');
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fila++;

                $sheet->mergeCells("A{$fila}:H{$fila}");
                $sheet->setCellValue("A{$fila}", "GESTIÓN {$this->gestion}");
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fila++;

                $sheet->mergeCells("A{$fila}:H{$fila}");
                $sheet->setCellValue("A{$fila}", 'Usuario: ' . mb_convert_case(mb_strtolower($this->nombreUsuario), MB_CASE_TITLE, 'UTF-8') . '  ·  Fecha: ' . now()->translatedFormat('d \d\e F \d\e Y'));
                $sheet->getStyle("A{$fila}")->getFont()->setItalic(true)->setSize(9);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fila += 2;

                $filaEncabezado = $fila;
                $headers = [
                    'N°',
                    'Detalle',
                    'Solicitud de desembolsos de Recursos Humanos en Bs.',
                    'Número de beneficiarios PCD.',
                    'Monto total pagado a PCD. en Bs.',
                    'Número de pagos a PCD beneficiadas',
                    'Cantidad de personas que no han cobrado el bono PCD.',
                    'Total de saldos no cancelados',
                ];
                foreach ($headers as $i => $h) {
                    $col = chr(65 + $i);
                    $sheet->setCellValue("{$col}{$filaEncabezado}", $h);
                }
                $sheet->getStyle("A{$filaEncabezado}:H{$filaEncabezado}")->getFont()->setBold(true);
                $sheet->getStyle("A{$filaEncabezado}:H{$filaEncabezado}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
                $sheet->getStyle("A{$filaEncabezado}:H{$filaEncabezado}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getStyle("A{$filaEncabezado}:H{$filaEncabezado}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getRowDimension($filaEncabezado)->setRowHeight(32);
                $fila++;

                $n = 1;
                $totales = [
                    'presupuesto' => 0.0,
                    'beneficiarios' => 0,
                    'pagado' => 0.0,
                    'numPagos' => 0,
                    'noPagados' => 0,
                    'saldo' => 0.0,
                ];

                foreach ($filas as $item) {
                    $monto = (float) $item->presupuesto_mes;
                    $habilitados = (int) $item->cantidad_habilitadas;
                    $numPagos = (int) $item->cantidad_total_pagos;
                    $noPagados = (int) $item->cantidad_no_pagados;

                    $presupuestoFila = $monto * $habilitados;
                    $pagadoFila = $monto * $numPagos;
                    $saldoFila = $monto * $noPagados;

                    $sheet->setCellValue("A{$fila}", $n);
                    $sheet->setCellValue("B{$fila}", "SOLICITUD ECONÓMICA {$this->nombreMes($item->mes)} {$item->gestion}");
                    $sheet->setCellValueExplicit("C{$fila}", number_format($presupuestoFila, 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue("D{$fila}", $habilitados);
                    $sheet->setCellValueExplicit("E{$fila}", number_format($pagadoFila, 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue("F{$fila}", $numPagos);
                    $sheet->setCellValue("G{$fila}", $noPagados);
                    $sheet->setCellValueExplicit("H{$fila}", number_format($saldoFila, 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                    $rango = "A{$fila}:H{$fila}";
                    $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$fila}:G{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $totales['presupuesto'] += $presupuestoFila;
                    $totales['beneficiarios'] += $habilitados;
                    $totales['pagado'] += $pagadoFila;
                    $totales['numPagos'] += $numPagos;
                    $totales['noPagados'] += $noPagados;
                    $totales['saldo'] += $saldoFila;

                    $n++;
                    $fila++;
                }

                $sheet->mergeCells("A{$fila}:B{$fila}");
                $sheet->setCellValue("A{$fila}", 'TOTAL GENERAL');
                $sheet->setCellValueExplicit("C{$fila}", number_format($totales['presupuesto'], 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("D{$fila}", $totales['beneficiarios']);
                $sheet->setCellValueExplicit("E{$fila}", number_format($totales['pagado'], 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->setCellValue("F{$fila}", $totales['numPagos']);
                $sheet->setCellValue("G{$fila}", $totales['noPagados']);
                $sheet->setCellValueExplicit("H{$fila}", number_format($totales['saldo'], 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                $rangoTotal = "A{$fila}:H{$fila}";
                $sheet->getStyle($rangoTotal)->getFont()->setBold(true);
                $sheet->getStyle($rangoTotal)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');
                $sheet->getStyle($rangoTotal)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("D{$fila}:G{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane('A' . ($filaEncabezado + 1));
            },
        ];
    }
}
