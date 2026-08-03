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
 * Una planilla (mes normal o retroactivo) del reporte de pagados/no pagados.
 * Reproduce en Excel los mismos colores y secciones que ya usa el PDF
 * (useReportePagadosPDF.js): fila verde = pagado, amarilla = baja definitiva,
 * azul oscuro = baja temporal, y un bloque de totales al final.
 */
class ReportePagadosSheet implements FromArray, WithEvents, WithTitle, WithColumnWidths
{
    private const MESES = [
        1 => 'ENERO', 2 => 'FEBRERO', 3 => 'MARZO', 4 => 'ABRIL',
        5 => 'MAYO', 6 => 'JUNIO', 7 => 'JULIO', 8 => 'AGOSTO',
        9 => 'SEPTIEMBRE', 10 => 'OCTUBRE', 11 => 'NOVIEMBRE', 12 => 'DICIEMBRE',
    ];

    protected Collection $datos;
    protected $gestion;
    protected $mes;
    protected float $monto;
    protected bool $esRetro;

    public function __construct($datos, $gestion, $mes, float $monto, bool $esRetro = false)
    {
        $this->datos = collect($datos);
        $this->gestion = $gestion;
        $this->mes = $mes;
        $this->monto = $monto;
        $this->esRetro = $esRetro;
    }

    public function title(): string
    {
        return $this->esRetro ? 'Retroactivo' : 'Mes normal';
    }

    public function columnWidths(): array
    {
        return ['A' => 6, 'B' => 14, 'C' => 40, 'D' => 20, 'E' => 14, 'F' => 16, 'G' => 32];
    }

    // El contenido real se escribe en el evento AfterSheet (encabezado con
    // logos de texto, colores condicionales por fila, bloque de totales),
    // por eso array() solo entrega una fila vacía para satisfizar la
    // interfaz FromArray que exige el paquete.
    public function array(): array
    {
        return [[]];
    }

    private function nombreMes(): string
    {
        return self::MESES[(int) $this->mes] ?? 'MES';
    }

    // Un registro está "anulado" solo si tiene boleta Y pago_flag === 0
    // (misma regla que el PDF).
    private function filasValidas(): Collection
    {
        return $this->datos
            ->map(fn($item) => (object) $item)
            ->filter(function ($item) {
                $anulado = !empty($item->numero_boleta) && (int) ($item->pago_flag ?? 1) === 0;
                return !$anulado;
            })
            ->values();
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $filas = $this->filasValidas();

                // Los logos faltaban por completo en esta hoja (bug reportado:
                // "falta de imágenes"). Mismo criterio que el resto de los
                // Exports: sacaba.png a la izquierda, sigamos.png a la derecha.
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
                    $drawingDerecho->setOffsetX(60);
                    $drawingDerecho->setOffsetY(3);
                    $drawingDerecho->setWorksheet($sheet);
                }

                $fila = 1;

                $sheet->mergeCells("A{$fila}:G{$fila}");
                $sheet->setCellValue("A{$fila}", 'PLANILLA DE PAGO DE BONO MENSUAL A FAVOR DE LAS PERSONAS CON DISCAPACIDAD');
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(11);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fila++;

                $sheet->mergeCells("A{$fila}:G{$fila}");
                $subtitulo = "GRAVE Y MUY GRAVE MES DE {$this->nombreMes()} GESTIÓN {$this->gestion}"
                    . ($this->esRetro ? ' - RETROACTIVO' : '');
                $sheet->setCellValue("A{$fila}", $subtitulo);
                $sheet->getStyle("A{$fila}")->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle("A{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $fila += 2;

                $filaEncabezado = $fila;
                $headers = ['N°', 'C.I.', 'APELLIDOS Y NOMBRES P.C.D.', 'GRADO DE DISCAPACIDAD', 'N° BOLETA', 'MONTO A PAGAR (BS.)', 'OBSERVACIONES'];
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
                $pagados = 0;
                $montoPagados = 0.0;
                $bajaDefinitiva = 0;
                $bajaTemporal = 0;
                $sinPago = 0;

                foreach ($filas as $item) {
                    $estado = $item->estado_actual ?? null;
                    $esPagado = !empty($item->numero_boleta);

                    $nombre = trim(($item->apellido_persona ?? '') . ' ' . ($item->nombre_persona ?? ''));
                    $nombre = $nombre !== '' ? $nombre : ($item->nombre_completo ?? '');

                    $observacion = $estado === 'baja_temporal'
                        ? 'PADRE FUNCIONARIO TRABAJANDO CON ITEM'
                        : ($estado === 'baja_definitiva'
                            ? 'FALLECIO'
                            : ($esPagado ? 'PAGADO' : ''));

                    $sheet->setCellValue("A{$fila}", $n);
                    $sheet->setCellValue("B{$fila}", $item->ci_persona ?? '');
                    $sheet->setCellValue("C{$fila}", mb_strtoupper($nombre));
                    $sheet->setCellValue("D{$fila}", 'GRAVE MUY GRAVE');
                    $sheet->setCellValue("E{$fila}", $item->numero_boleta ?? '');
                    // Monto a pagar: se muestra en todas las filas, incluidas las bajas.
                    $montoAMostrar = $esPagado ? (float) ($item->monto_pago ?? 0) : $this->monto;
                    $sheet->setCellValueExplicit("F{$fila}", number_format($montoAMostrar, 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    $sheet->setCellValue("G{$fila}", $observacion);

                    $rango = "A{$fila}:G{$fila}";
                    $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("A{$fila}:B{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    $sheet->getStyle("D{$fila}:G{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    if ($estado === 'baja_definitiva') {
                        $sheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFFF00');
                        $sheet->getStyle($rango)->getFont()->getColor()->setRGB('FF0000');
                        $bajaDefinitiva++;
                    } elseif ($estado === 'baja_temporal') {
                        $sheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('002060');
                        $sheet->getStyle($rango)->getFont()->getColor()->setRGB('00B0F0');
                        $bajaTemporal++;
                    } elseif ($esPagado) {
                        $sheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BBF7D0');
                        $sheet->getStyle($rango)->getFont()->getColor()->setRGB('15803D');
                        $pagados++;
                        $montoPagados += (float) ($item->monto_pago ?? 0);
                    } else {
                        $sinPago++;
                    }

                    $n++;
                    $fila++;
                }

                $totalPersonas = $filas->count();
                $montoBajaDefinitiva = $bajaDefinitiva * $this->monto;
                $montoBajaTemporal = $bajaTemporal * $this->monto;
                $montoSinPago = $sinPago * $this->monto;
                $totalAPagar = $montoPagados + $montoSinPago;

                // Sin fila en blanco antes de los totales (mismo bug que en
                // Beneficiario: había un $fila++ suelto acá).
                $this->filaTotal($sheet, $fila++, "TOTAL PLANILLA GENERAL: {$totalPersonas} PERSONAS (" . number_format($this->monto, 0, ',', '.') . " C/U)", $totalPersonas * $this->monto, 'FFFFFF');
                $this->filaTotal($sheet, $fila++, "{$bajaDefinitiva} PERSONAS FALLECIERON HASTA LA FECHA, NO COBRARÁN DEL MES DE {$this->nombreMes()} {$this->gestion}", $montoBajaDefinitiva, 'FFFFFF');
                $this->filaTotal($sheet, $fila++, "{$bajaTemporal} FUNCIONARIOS PÚBLICOS CON ITEM (MADRE O PADRE O TUTOR) NO ACCEDEN AL BONO DEL MES DE {$this->nombreMes()} {$this->gestion}", $montoBajaTemporal, 'FFFFFF');
                $this->filaTotal($sheet, $fila++, "TOTAL PAGADOS MES DE {$this->nombreMes()} {$this->gestion} ({$pagados} PERSONAS)", $montoPagados, 'BBF7D0');
                $this->filaTotal($sheet, $fila++, "TOTAL PENDIENTES DE PAGO MES DE {$this->nombreMes()} {$this->gestion} ({$sinPago} PERSONAS)", $montoSinPago, 'FEF3C7');
                $this->filaTotal($sheet, $fila, "TOTAL A PAGAR MES DE {$this->nombreMes()} {$this->gestion} (PLANILLA - BAJAS)", $totalAPagar, '87CEEB', true);

                $sheet->freezePane('A' . ($filaEncabezado + 1));
            },
        ];
    }

    private function filaTotal($sheet, int $fila, string $label, float $monto, string $color, bool $destacado = false): void
    {
        $sheet->mergeCells("A{$fila}:E{$fila}");
        $sheet->setCellValue("A{$fila}", $label);
        $sheet->setCellValueExplicit("F{$fila}", number_format($monto, 0, ',', '.'), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

        $rango = "A{$fila}:G{$fila}";
        $sheet->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($destacado ? '87CEEB' : $color);
        $sheet->getStyle($rango)->getFont()->setBold(true)->setSize($destacado ? 11 : 8);
        $sheet->getStyle($rango)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A{$fila}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("F{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
