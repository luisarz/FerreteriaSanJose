<?php

namespace App\Exports;

use App\Models\Inventory;
use App\Models\Kardex;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class inventoryExport implements FromCollection, WithHeadings, WithEvents, WithColumnFormatting
{
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected float $totalGravada = 0;
    protected float $totalDebitoFiscal = 0;
    protected float $totalVenta = 0;

    protected Collection $resultados;
protected string $update;
    public function __construct(string $update,string $startDate, string $endDate)
    {
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
        $this->update = $update;
        $this->resultados = collect();
    }

    public function headings(): array
    {
        return [
            'ITEM',
            'PRODUCTO',
            'CATEGORIA',
            'UNIDAD MEDIDA',
            'SALDO ANTERIOR',
            'ENTRADA',
            'SALIDA',
            'EXISTENCIA',
            'C. VENTA',
            'C. T. VENTA',
            'C. COMPRA',
            'COSTO TOTAL',
        ];
    }

    public function collection(): Collection
    {
        set_time_limit(0);

        // 📌 Traer datos agrupados para TODOS los inventarios

        // Entradas/Salidas ANTERIORES
        $anteriores = Kardex::selectRaw("
                inventory_id,
                COALESCE(SUM(stock_in),0) as entradas,
                COALESCE(SUM(stock_out),0) as salidas,
                COALESCE(SUM(money_out),0) as dinero_salidas
            ")
            ->whereDate('date', '<', $this->startDate)
            ->groupBy('inventory_id')
            ->get()
            ->keyBy('inventory_id');

        // Movimientos en el período
        $movimientos = Kardex::selectRaw("
                inventory_id,
                COALESCE(SUM(stock_in),0) as entradas,
                COALESCE(SUM(stock_out),0) as salidas,
                COALESCE(SUM(money_out),0) as dinero_salidas
            ")
            ->whereBetween('date', [$this->startDate, $this->endDate])
            ->groupBy('inventory_id')
            ->get()
            ->keyBy('inventory_id');

        // Últimos costos/precios (subquery con max fecha)
        $ultimos = DB::table('kardex as k')
            ->join(DB::raw("
                (SELECT inventory_id, MAX(date) as last_date
                 FROM kardex
                 WHERE date < '" . $this->endDate->toDateString() . "'
                 GROUP BY inventory_id) latest
            "), function ($join) {
                $join->on('k.inventory_id', '=', 'latest.inventory_id')
                    ->on('k.date', '=', 'latest.last_date');
            })
            ->select('k.inventory_id', 'k.purchase_price', 'k.sale_price')
            ->get()
            ->keyBy('inventory_id');

$warehouse_id=auth()->user()->employee->branch_id;
        // 📌 Procesar inventarios en chunks
        Inventory::with('product.category', 'product.unitmeasurement')
            ->where('is_active', true)
            ->where('branch_id', $warehouse_id)
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunk(500, function ($inventarios) use ($anteriores, $movimientos, $ultimos) {
                foreach ($inventarios as $inv) {
                    $ant = $anteriores[$inv->id] ?? null;
                    $mov = $movimientos[$inv->id] ?? null;
                    $ulti = $ultimos[$inv->id] ?? null;

                    $entradasAnt = $ant->entradas ?? 0;
                    $salidasAnt = $ant->salidas ?? 0;
                    $saldoAnterior = $entradasAnt - $salidasAnt;

                    $entradaPeriodo = $mov->entradas ?? 0;
                    $salidaPeriodo = $mov->salidas ?? 0;

                    $nuevoSaldo = ($entradaPeriodo + $saldoAnterior) - $salidaPeriodo;

                    $ultimoCosto = $ulti->purchase_price ?? 0;
                    $precioVentaUnidad = $ulti->sale_price ?? 0;

                    $costoTotal = $ultimoCosto * $nuevoSaldo;
                    $ventaTotal = $precioVentaUnidad * $nuevoSaldo;

                    // acumular totales
                    $this->totalVenta += $ventaTotal;

//                    $inventarios=Inventory::find($inv->id);
                    if ($this->update == '1') {
                        $inv->stock = $nuevoSaldo;
                        $inv->save();
                    }
                    //dd($inventarios);

                    $this->resultados->push([
                        $inv->id,                                           // ITEM
                        $inv->product->name ?? 'S/N',                      // PRODUCTO
                        $inv->product->category->name ?? '',               // CATEGORIA
                        $inv->product->unitmeasurement->description ?? '', // UNIDAD MEDIDA
                        number_format($saldoAnterior, 2, '.', ''),               // SALDO ANTERIOR
                        number_format($entradaPeriodo, 2, '.', ''),              // ENTRADA
                        number_format($salidaPeriodo, 2, '.', ''),               // SALIDA
                        number_format($nuevoSaldo, 2, '.', ''),                  // EXISTENCIA
                        number_format($precioVentaUnidad, 2, '.', ''),           // C. VENTA
                        number_format($ventaTotal, 2, '.', ''),                  // C. T. VENTA
                        number_format($ultimoCosto, 2, '.', ''),                 // C. COMPRA
                        number_format($costoTotal, 2, '.', ''),                  // COSTO TOTAL
                    ]);

                }
            });

        return $this->resultados;
    }

    public function columnFormats(): array
    {
        return [
            'E' => '#,##0.00',
            'F' => '#,##0.00',
            'G' => '#,##0.00',
            'H' => '#,##0.00',
            'I' => NumberFormat::FORMAT_ACCOUNTING_USD,
            'J' => NumberFormat::FORMAT_ACCOUNTING_USD,
            'K' => NumberFormat::FORMAT_ACCOUNTING_USD,
            'L' => NumberFormat::FORMAT_ACCOUNTING_USD,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $footerRow = $lastRow + 1;

                $sheet->setCellValue('A' . $footerRow, 'Totales:');
                $sheet->setCellValue('J' . $footerRow, $this->totalVenta);

                $sheet->getStyle('A' . $footerRow . ':L' . $footerRow)->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                ]);
            },
        ];
    }
}
