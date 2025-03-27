<?php

namespace App\Exports;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExportCCF implements FromCollection, WithHeadings, WithEvents
{
    protected string $documentType;
    protected Carbon $startDate;
    protected Carbon $endDate;
    protected float $totalGravada = 0;
    protected float $totalDebitoFiscal = 0;
    protected float $totalVenta = 0;

    public function __construct(string $documentType, string $startDate, string $endDate)
    {
        $this->documentType = $documentType;
        $this->startDate = Carbon::parse($startDate);
        $this->endDate = Carbon::parse($endDate);
    }

    public function headings(): array
    {
        return [
            'Fecha Emisión',
            'Clase de Documento',
            'Tipo de Documento',
            'N Resolucion',
            'Serie',
            'Numero Correlativo',
            'Control Interno',
            'NRC o NIT',
            'Nombre del Contribuyente',
            'Exenta',
            'No Sujeta',
            'Gravada Local',
            'Débito Fiscal',
            'Venta a Cuenta de Terceros',
            'Debi. Fiscal a Cuenta de Terceros',
            'Total Venta',
            'DUI',
            'Numero Anexo',
        ];
    }

    public function collection(): Collection
    {
        $sales = Sale::select(
            'id',
            'customer_id',
            'operation_date as fecha',
            'sale_total as venta_gravada',
            'net_amount as neto',
            'taxe as iva',
            'sale_total as total'
        )
            ->where('is_dte', '1')
            ->where('document_type_id', 2)
            ->whereBetween('operation_date', [$this->startDate, $this->endDate])
            ->orderBy('operation_date', 'asc')
            ->with(
                ['dteProcesado' => function ($query) {
                    $query->select('sales_invoice_id', 'num_control', 'selloRecibido', 'codigoGeneracion')
                        ->whereNotNull('selloRecibido');
                },
                    'customer' => function ($query) {
                        $query->select('id', 'name', 'last_name', 'document_type_id', 'nit', 'dui', 'nrc');
                    }
                ])
            ->get()
            ->map(function ($sale) {
                // Sumar los totales para el footer
                $this->totalGravada += $sale->neto;
                $this->totalDebitoFiscal += $sale->iva;
                $this->totalVenta += $sale->total;

                return [
                    'fecha' => $sale->fecha,
                    'clase_documento' => $this->documentType,
                    'tipo_documento' => 'Factura', // Ajusta según sea necesario
                    'resolucion' => $sale->dteProcesado->num_control ?? null,
                    'serie' => $sale->dteProcesado->selloRecibido ?? null,
                    'correlativo' => $sale->dteProcesado->codigoGeneracion ?? null,
                    'control_interno' => '', // Ajusta según sea necesario
                    'nrc_nit' => $sale->customer->nrc ?? $sale->customer->nit,
                    'contribuyente' => $sale->customer->name . ' ' . $sale->customer->last_name,
                    'exenta' => 0,
                    'no_sujeta' => 0,
                    'gravada_local' => $sale->neto,
                    'debito_fiscal' => $sale->iva,
                    'venta_cuenta_terceros' => '', // Ajusta según sea necesario
                    'debi_fiscal_cuenta_terceros' => '', // Ajusta según sea necesario
                    'total_venta' => $sale->total,
                    'dui' => $sale->customer->dui,
                    'anexo' => 1,
                ];
            });

        return $sales;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Obtener la hoja activa
                $sheet = $event->sheet->getDelegate();

                // Obtener el número de la última fila con datos
                $lastRow = $sheet->getHighestRow();

                // Agregar el footer con los totales
                $footerRow = $lastRow + 1;
                $sheet->setCellValue('A' . $footerRow, 'Totales:');
                $sheet->setCellValue('L' . $footerRow, $this->totalGravada); // Gravada Local
                $sheet->setCellValue('M' . $footerRow, $this->totalDebitoFiscal); // Débito Fiscal
                $sheet->setCellValue('P' . $footerRow, $this->totalVenta); // Total Venta

                // Formatear las celdas del footer
                $sheet->getStyle('A' . $footerRow . ':R' . $footerRow)->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                ]);
            },
        ];
    }
}