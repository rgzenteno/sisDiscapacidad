<?php

namespace App\Exports;

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
 * Reporte de Arqueo de Caja de un usuario (tab "Mis Pagos" → botón
 * "Cerrar Caja"), en Excel. Reproduce el mismo modelo que
 * useReporteArqueoCajaPDF.js: 3 filas fijas (Total Pagado / Anulado /
 * Total No Pagado) + fila de Total General + línea de firma.
 */
class ArqueoCajaExport implements FromArray, WithEvents, WithTitle, WithColumnWidths
{
    private const MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    protected object $data;
    protected string $nombreUsuario;
    protected ?string $cargo;

    public function __construct(array $data, string $nombreUsuario, ?string $cargo = null)
    {
        $this->data = (object) $data;
        $this->nombreUsuario = $nombreUsuario;
        $this->cargo = $cargo;
    }

    public function title(): string
    {
        return 'Arqueo de Caja';
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 30, 'C' => 22, 'D' => 18];
    }

    public function array(): array
    {
        return [[]];
    }

    private function nombreMes(): string
    {
        $i = (int) ($this->data->mes ?? 0);
        return self::MESES[$i] ?? '';
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
                    $drawingDerecho->setOffsetX(40);
                    $drawingDerecho->setOffsetY(3);
                    $drawingDerecho->setWorksheet($sheet);
                }

                $anulados = (int) ($this->data->cantidad_anulados ?? 0);
                $pagados = (int) ($this->data->cantidad_total_pagos ?? 0) - $anulados;
                $unitario = (float) ($this->data->monto_pago ?? 0);
                $presupuestoAsignado = (float) ($this->data->presupuesto_asignado ?? 0);

                // "No pagados" no viene del frontend: se deriva del
                // presupuesto asignado (mismo criterio que el PDF de
                // referencia, useReporteArqueoCajaPDF.js) — cuántos
                // beneficiarios le corresponden en total a este cajero
                // (presupuesto / monto unitario) menos los que ya tiene
                // pagados o anulados.
                $totalAsignados = $unitario > 0 ? (int) round($presupuestoAsignado / $unitario) : 0;
                $noPagados = max(0, $totalAsignados - $pagados - $anulados);

                $totalPag = (float) ($this->data->total_pagado ?? 0);
                $totalNoPag = $noPagados * $unitario;
                $totalGral = $totalPag + $totalNoPag;
                $cantGral = $pagados + $anulados + $noPagados;

                // Rango del período — mismo cálculo que rangoMes() en el PDF
                // de referencia (useReporteArqueoCajaPDF.js).
                $mesPad = str_pad((string) (int) ($this->data->mes ?? 0), 2, '0', STR_PAD_LEFT);
                $gestionArqueo = (string) ($this->data->gestion ?? '');
                $ultimoDia = (int) date('t', mktime(0, 0, 0, (int) ($this->data->mes ?? 1), 1, (int) ($this->data->gestion ?? date('Y'))));
                $periodoDesde = "01/{$mesPad}/{$gestionArqueo}";
                $periodoHasta = "{$ultimoDia}/{$mesPad}/{$gestionArqueo}";

                $fila = 1;

                // Mismo encabezado que drawHeader() en el PDF de referencia:
                // título, subtítulo, fecha y hora del reporte / monto x
                // beneficiario, periodo de arqueo / presupuesto asignado
                // (izquierda/derecha en la misma fila), usuario responsable,
                // cargo y mes de pago. Monto pagado/restante no se repiten acá
                // — ya están en la tabla y en "Total general" más abajo.
                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}", 'REPORTE DE ARQUEO DE CAJA');
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(12);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fila++;

                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}", 'Sistema Municipal de Gestión de Beneficios para Personas con Discapacidad');
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(9);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fila++;

                $sheet->mergeCells("A{$fila}:B{$fila}");
                $sheet->setCellValue("A{$fila}", 'Fecha y hora del Reporte: ' . now()->format('d/m/Y H:i'));
                $sheet->getStyle("A{$fila}")->getFont()->setSize(9);
                $sheet->mergeCells("C{$fila}:D{$fila}");
                $sheet->setCellValue("C{$fila}", 'Monto x Beneficiario: Bs ' . number_format($unitario, 0, ',', '.'));
                $sheet->getStyle("C{$fila}")->getFont()->setSize(9);
                $sheet->getStyle("C{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $fila++;

                $sheet->mergeCells("A{$fila}:B{$fila}");
                $sheet->setCellValue("A{$fila}", "Periodo de Arqueo: {$periodoDesde} al {$periodoHasta}");
                $sheet->getStyle("A{$fila}")->getFont()->setSize(9);
                $sheet->mergeCells("C{$fila}:D{$fila}");
                $sheet->setCellValue("C{$fila}", 'Presupuesto Asignado: Bs ' . number_format($presupuestoAsignado, 0, ',', '.'));
                $sheet->getStyle("C{$fila}")->getFont()->setSize(9);
                $sheet->getStyle("C{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $fila++;

                $responsable = "GESTIÓN {$this->data->gestion} · " . mb_strtoupper($this->nombreMes()) . '  ·  Usuario Responsable: ' . mb_strtoupper($this->nombreUsuario);
                if ($this->cargo) {
                    $responsable .= '  ·  Cargo: ' . mb_strtoupper($this->cargo);
                }
                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}", $responsable);
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(9);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fila += 2;

                $filaEncabezado = $fila;
                $headers = ['N°', 'Descripción', 'Cantidad de Beneficiarios', 'Total (Bs)'];
                foreach ($headers as $i => $h) {
                    $col = chr(65 + $i);
                    $sheet->setCellValue("{$col}{$filaEncabezado}", $h);
                }
                $sheet->getStyle("A{$filaEncabezado}:D{$filaEncabezado}")->getFont()->setBold(true);
                $sheet->getStyle("A{$filaEncabezado}:D{$filaEncabezado}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6E6');
                $sheet->getStyle("A{$filaEncabezado}:D{$filaEncabezado}")->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setWrapText(true);
                $sheet->getStyle("A{$filaEncabezado}:D{$filaEncabezado}")->getBorders()
                    ->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $fila++;

                $filas = [
                    [1, 'Total Pagado', $pagados, $totalPag],
                    [2, 'Anulado', $anulados, 0],
                    [3, 'Total No Pagado', $noPagados, $totalNoPag],
                ];

                foreach ($filas as [$n, $desc, $cantidad, $total]) {
                    $sheet->setCellValue("A{$fila}", $n);
                    $sheet->setCellValue("B{$fila}", $desc);
                    $sheet->setCellValue("C{$fila}", $cantidad);
                    $sheet->setCellValueExplicit("D{$fila}", number_format($total, 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                    $rango = "A{$fila}:D{$fila}";
                    $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$fila}:D{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $fila++;
                }

                $sheet->mergeCells("A{$fila}:B{$fila}");
                $sheet->setCellValue("A{$fila}", 'Total general');
                $sheet->setCellValue("C{$fila}", $cantGral);
                $sheet->setCellValueExplicit("D{$fila}", number_format($totalGral, 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

                $rangoTotal = "A{$fila}:D{$fila}";
                $sheet->getStyle($rangoTotal)->getFont()->setBold(true);
                $sheet->getStyle($rangoTotal)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F0F0F0');
                $sheet->getStyle($rangoTotal)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("C{$fila}:D{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fila += 3;

                $sheet->mergeCells("B{$fila}:C{$fila}");
                $sheet->getStyle("B{$fila}:C{$fila}")->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN);
                $fila++;
                $sheet->mergeCells("B{$fila}:C{$fila}");
                $sheet->setCellValue("B{$fila}", 'Firma y Sello Cajer@');
                $sheet->getStyle("B{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane('A' . ($filaEncabezado + 1));
            },
        ];
    }
}
