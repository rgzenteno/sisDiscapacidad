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
 * Reporte individual de pagos de un usuario (tab "Mis Pagos" y el modal de
 * detalle por usuario en Resumen General), en Excel. Reproduce el mismo
 * modelo que useReportePagosIndividualPDF.js: N°, Nombre, C.I., Distrito,
 * N° Boleta, Monto, Fecha — filas anuladas en rojo/negrita, y un total al
 * final con el monto pagado.
 */
class PagosIndividualExport implements FromArray, WithEvents, WithTitle, WithColumnWidths
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
        return 'Mis Pagos';
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 32, 'C' => 16, 'D' => 14, 'E' => 14, 'F' => 16, 'G' => 16];
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
                    $drawingDerecho->setCoordinates('F1');
                    $drawingDerecho->setOffsetX(40);
                    $drawingDerecho->setOffsetY(3);
                    $drawingDerecho->setWorksheet($sheet);
                }

                // Mismo bloque informativo que drawInfoBlock() en el PDF de
                // referencia (useReportePagosIndividualPDF.js): NO hay un título
                // grande "REPORTE DE PAGOS" en el PDF — es Gestión/Meses (o
                // Período) + usuario + Monto Total Pagado, todo en una línea.
                $fila = 1;

                if ($this->fechaDesde && $this->fechaHasta) {
                    $periodo = "Período: {$this->fechaDesde} al {$this->fechaHasta}";
                } else {
                    $periodo = "Gestión: {$this->gestion}    Meses: " . mb_strtoupper($this->nombreMes());
                }
                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}", $periodo);
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(9);

                $sheet->mergeCells("E{$fila}:G{$fila}");
                $sheet->setCellValue("E{$fila}", 'usuario: ' . mb_strtoupper($this->nombreUsuario));
                $sheet->getStyle("E{$fila}")->getFont()->setBold(true)->setSize(9);
                $fila++;

                $sheet->mergeCells("A{$fila}:G{$fila}");
                $sheet->setCellValueExplicit("A{$fila}", 'Monto Total Pagado: ' . number_format($this->montoTotal(), 0, ',', '.') . ' Bs', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(9);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                $fila += 2;

                $filaEncabezado = $fila;
                $headers = ['N°', 'Nombre Completo', 'C.I.', 'Distrito', 'N° Boleta', 'Monto Pago (Bs)', 'Fecha de Pago'];
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

                    $sheet->setCellValue("A{$fila}", $n);
                    $sheet->setCellValue("B{$fila}", mb_strtoupper($nombre));
                    $sheet->setCellValue("C{$fila}", $item->ci_persona ?? '');
                    $sheet->setCellValue("D{$fila}", $item->distrito ?? '');
                    $sheet->setCellValue("E{$fila}", $item->numero_boleta ?? '');
                    if ($anulado) {
                        $sheet->setCellValue("F{$fila}", '¡Anulado!');
                    } else {
                        $sheet->setCellValueExplicit("F{$fila}", number_format((float) ($item->monto ?? 0), 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                    $sheet->setCellValue("G{$fila}", $item->fecha_pago ?? '');

                    $rango = "A{$fila}:G{$fila}";
                    $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A{$fila}:A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$fila}:G{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    if ($anulado) {
                        $sheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FECACA');
                        $sheet->getStyle($rango)->getFont()->setBold(true)->getColor()->setRGB('B91C1C');
                    }

                    $n++;
                    $fila++;
                }

                // Sin fila en blanco entre la última fila de datos y el total
                // (mismo bug que en Beneficiario: había un $fila++ suelto acá).
                $sheet->mergeCells("A{$fila}:E{$fila}");
                $sheet->setCellValue("A{$fila}", 'MONTO TOTAL PAGADO');
                $sheet->setCellValueExplicit("F{$fila}", number_format($this->montoTotal(), 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
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
