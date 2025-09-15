<?php

namespace App\Exports;

use App\Models\Kardex;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Events\AfterSheet;

class InventoryMovimentExport implements FromCollection, WithHeadings, WithEvents, WithColumnFormatting
{
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected string $productCode;
    protected Collection $resultados;

    public function __construct(string $code, string $startDate, string $endDate)
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
        $this->productCode = $code;
        $this->resultados = collect();
    }

    public function headings(): array
    {
        return [
            'ITEM', 'FECHA', 'COMPROBANTE', 'OPERACION', 'RAZON SOCIAL', 'NACIONALIDAD',
            'PRODUCTO', 'UNIDAD MEDIDA', 'SALDO ANTERIOR', 'ENTRADA', 'SALIDA',
            'EXISTENCIA', 'COSTO', 'COSTO PROMEDIO', 'DEBE', 'HABER', 'SALDO', 'CODIGO'
        ];
    }

    private function toFloat($value): float {
        return is_numeric($value) ? (float)$value : 0.0;
    }

    public function collection(): Collection
    {
        set_time_limit(0);

        $rows = Kardex::with(['inventory', 'inventory.product', 'inventory.product.unitmeasurement'])
            ->where('inventory_id', $this->productCode)
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $item = 1;
        $saldoAnteriorRep = 0.0;
        $existencia = 0.0;
        $saldoDineroTotal = 0.0;
        $costoPromedioLineaAnterior = 0.0;
        $ultimoCostoUnitario = 0.0;

        foreach ($rows as $row) {
            $entrada = $this->toFloat($row->stock_in);
            $salida = $this->toFloat($row->stock_out);
            $costo = $this->toFloat($row->purchase_price);

            $producto = $row->inventory->product ?? null;
            $nombreProducto = $producto ? $producto->name : 'S/N';
            $unidadMedida = $producto && $producto->unitmeasurement ? $producto->unitmeasurement->description : 'S/N';

            $debe = 0.0;
            $haber = 0.0;

            if ($row->operation_type === "INVENTARIO INICIAL") {
                $saldoAnteriorRep = $this->toFloat($row->previous_stock);
                $existencia = $this->toFloat($row->stock_actual);
                $ultimoCostoUnitario = $costo;
                $saldoDineroTotal = $existencia * $ultimoCostoUnitario;
                $costoPromedio = $existencia > 0 ? $saldoDineroTotal / $existencia : $costoPromedioLineaAnterior;
                $costoPromedioLineaAnterior = $costoPromedio;
                $debe = $existencia * $ultimoCostoUnitario;
            } else {
                if ($entrada > 0) { // COMPRA
                    $ultimoCostoUnitario = $costo;
                    $debe = $entrada * $ultimoCostoUnitario;
                    $existencia += $entrada;
                    $saldoDineroTotal += $debe;
                    $costoPromedio = $existencia > 0 ? $saldoDineroTotal / $existencia : 0.0;
                } elseif ($salida > 0) { // VENTA
                    $existencia -= $salida;
                    $costoPromedio = $costoPromedioLineaAnterior;
                    $haber = $salida * $costoPromedio;
                    $saldoDineroTotal -= $haber;
                }
                $costoPromedioLineaAnterior = $costoPromedio;
            }

            $this->resultados->push([
                'ITEM' => $item++,
                'FECHA' => $row->date ?? '',
                'COMPROBANTE' => $row->document_number ?? '',
                'OPERACION' => $row->operation_type ?? '',
                'RAZON SOCIAL' => $row->entity ?? '',
                'NACIONALIDAD' => $row->nationality ?? 'S/N',
                'PRODUCTO' => $nombreProducto,
                'UNIDAD MEDIDA' => $unidadMedida,
                'SALDO ANTERIOR' => number_format($saldoAnteriorRep, 2, '.', ''),
                'ENTRADA' => number_format($entrada, 2, '.', ''),
                'SALIDA' => number_format($salida, 2, '.', ''),
                'EXISTENCIA' => number_format($existencia, 2, '.', ''),
                'COSTO' => number_format($ultimoCostoUnitario, 2, '.', ''),
                'COSTO PROMEDIO' => number_format($costoPromedio, 2, '.', ''),
                'DEBE' => number_format($debe, 2, '.', ''),
                'HABER' => number_format($haber, 2, '.', ''),
                'SALDO' => number_format($saldoDineroTotal, 2, '.', ''),
                'CODIGO' => $this->productCode ?? '',
            ]);

            $saldoAnteriorRep = $existencia;
        }

        return $this->resultados;
    }

    public function columnFormats(): array
    {
        return [
            'I' => '#,##0.00',  // SALDO ANTERIOR
            'J' => '#,##0.00',  // ENTRADA
            'K' => '#,##0.00',  // SALIDA
            'L' => '#,##0.00',  // EXISTENCIA
            'M' => '#,##0.00',  // COSTO
            'N' => '#,##0.00',  // COSTO PROMEDIO
            'O' => '#,##0.00',  // DEBE
            'P' => '#,##0.00',  // HABER
            'Q' => '#,##0.00',  // SALDO
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();

                // Ajustar ancho automático
                foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Negrita para encabezados
                $sheet->getStyle('A1:R1')->getFont()->setBold(true);

                // Alinear numéricos a la derecha
                $sheet->getStyle('I2:R' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                // Resaltar en rojo los valores negativos de la columna EXISTENCIA (columna L)
                for ($row = 2; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('L'.$row)->getValue();
                    if ((float)$cellValue < 0) {
                        $sheet->getStyle('L'.$row)->getFont()->getColor()->setRGB('FF0000'); // rojo
                    }
                }
            },
        ];
    }
}
