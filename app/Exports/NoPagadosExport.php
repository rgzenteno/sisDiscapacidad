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
 * Reporte "Beneficiarios No Pagados" (tab "No Pagados" de Bandeja de Pagos),
 * en Excel. Mismo modelo que PagosTodosExport.php: título institucional
 * (PLANILLA NO PAGADOS...), tabla N°/C.I./Nombre/Grado de Discapacidad/Monto
 * No Pagado, y un total al final.
 */
class NoPagadosExport implements FromArray, WithEvents, WithTitle, WithColumnWidths
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
        return 'No Pagados';
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 16, 'C' => 34, 'D' => 22, 'E' => 16];
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
        return $this->datos->reduce(fn($s, $item) => $s + (float) ($item->monto ?? 0), 0.0);
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
                    $drawingDerecho->setOffsetX(30);
                    $drawingDerecho->setOffsetY(3);
                    $drawingDerecho->setWorksheet($sheet);
                }

                $fila = 1;

                $periodoTexto = ($this->fechaDesde && $this->fechaHasta)
                    ? "PERÍODO {$this->fechaDesde} AL {$this->fechaHasta}"
                    : 'MES DE ' . mb_strtoupper($this->nombreMes()) . " {$this->gestion}";

                $tituloLinea1 = 'PLANILLA NO PAGADOS BONO MENSUAL EN FAVOR DE LAS PERSONAS CON DISCAPACIDAD';
                $tituloLinea2 = "GRAVE MUY GRAVE {$periodoTexto}" . ($this->verRetro ? ' (RETROACTIVOS)' : '');

                foreach ([$tituloLinea1, $tituloLinea2] as $linea) {
                    $sheet->mergeCells("A{$fila}:E{$fila}");
                    $sheet->setCellValue("A{$fila}", $linea);
                    $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(9);
                    $sheet->getStyle("A{$fila}")->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                        ->setVertical(Alignment::VERTICAL_CENTER)
                        ->setWrapText(true);
                    $fila++;
                }

                if ($this->distrito) {
                    $sheet->mergeCells("A{$fila}:E{$fila}");
                    $sheet->setCellValue("A{$fila}", 'Distrito: ' . mb_strtoupper($this->distrito));
                    $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(8)->getColor()->setRGB('2563EB');
                    $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
                $fila++;

                $filaEncabezado = $fila;
                $headers = ['N°', 'C.I.', 'Nombre Completo', 'Grado de Discapacidad', 'Monto No Pagado (Bs)'];
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
                foreach ($this->datos as $item) {
                    $nombre = $item->nombre_persona ?? null
                        ? trim(($item->apellido_persona ?? '') . ' ' . ($item->nombre_persona ?? ''))
                        : ($item->nombre_completo ?? '');

                    $sheet->setCellValue("A{$fila}", $n);
                    $sheet->setCellValue("B{$fila}", $item->ci_persona ?? '');
                    $sheet->setCellValue("C{$fila}", mb_strtoupper($nombre));
                    $sheet->setCellValue("D{$fila}", 'GRAVE Y MUY GRAVE');
                    $sheet->setCellValue("E{$fila}", (float) ($item->monto ?? 0));
                    $sheet->getStyle("E{$fila}")->getNumberFormat()->setFormatCode('#,##0');

                    $rango = "A{$fila}:E{$fila}";
                    $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A{$fila}:B{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("C{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                    $sheet->getStyle("D{$fila}:E{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    $n++;
                    $fila++;
                }

                $sheet->mergeCells("A{$fila}:D{$fila}");
                $sheet->setCellValue("A{$fila}", "TOTAL NO PAGADOS {$periodoTexto}");
                $sheet->setCellValue("E{$fila}", $this->montoTotal());
                $sheet->getStyle("E{$fila}")->getNumberFormat()->setFormatCode('#,##0');
                $rangoTotal = "A{$fila}:E{$fila}";
                $sheet->getStyle($rangoTotal)->getFont()->setBold(true);
                $sheet->getStyle($rangoTotal)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BBF7D0');
                $sheet->getStyle($rangoTotal)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("E{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->freezePane('A' . ($filaEncabezado + 1));
            },
        ];
    }
}
