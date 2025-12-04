<?php

namespace App\Exports;

use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SalesExportFac implements FromCollection, WithHeadings, WithEvents, WithColumnFormatting
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
            'ID Venta',
            'Fecha',
            'Clase',
            'Tipo',
            'Sello de recepcion',
            'Codigo de generacion',
            'Numero de control',
            'NRC',
            'NIT',
            'DUI',
            'Razon Social',
            'Exenta',
            'No Sujeta',
            'Gravada Local',
            'Débito Fiscal',
            'Retencion 1%',
            'ISR 10%',
            'Total Venta',
            'Estado',
            'fecha MH'
        ];


    }

    public function collection(): Collection
    {
        $query = Sale::select(
            'id',
            'operation_date as fecha',
            'sale_total as venta_gravada',
            'net_amount as neto',
            'taxe as iva',
            'sale_total as total',
            'document_type_id',
            'document_internal_number',
            'seller_id',
            'customer_id',
            'operation_condition_id',
            'payment_method_id',
            'sale_status',
            'billing_model',
            'transmision_type',
            'is_dte',
            'is_hacienda_send'
        )
            ->where('is_dte', '1')
            ->whereBetween('operation_date', [$this->startDate, $this->endDate]);

        // Si se especifica tipo de documento, filtra; sino, trae todos los tipos DTE
        if (!empty($this->documentType)) {
            $query->whereIn('document_type_id', [$this->documentType]);
        } else {
            $query->whereIn('document_type_id', [1, 3, 5, 11, 14]); // Todos los tipos DTE
        }

        $sales = $query
            ->orderBy('operation_date', 'asc')
            ->with(['dteProcesado' => function ($query) {
                $query->select('sales_invoice_id', 'num_control', 'selloRecibido', 'codigoGeneracion', 'fhProcesamiento', 'estado');
            },
                'documenttype', 'customer', 'billingModel', 'salescondition', 'seller'])
            ->get()
            ->map(function ($sale) {
//                dd($sale);
                $persontype = $sale->customer->person_type_id ?? 1; // 1: Natural, 2: Jurídica

                $rawNit = $sale->customer->nit;
                $rawDui = $sale->customer->dui;

                $cleanNit = str_replace('-', '', $rawNit);
                $formatNit = str_repeat('0', strlen($cleanNit)); // Ejemplo: '00000000000000'
                $cleanDUI = str_replace('-', '', $rawDui);
                $formatDUI = str_repeat('0', strlen($cleanDUI)); // Ejemplo: '00000000000000'
                $nit = ($rawNit === "0000-000000-000-0") ? null
                    : '=TEXT("' . $cleanNit . '","' . $formatNit . '")';
                $dui = ($rawDui === "00000000-0") ? null
                    : '=TEXT("' . $cleanDUI . '","' . $formatDUI . '")';

                $nit_report = '';
                $dui_report = '';

                // Comparar valores sin formato, no fórmulas
                $isSame = $cleanNit === $cleanDUI;

                if ($persontype == 2) { // Jurídica
                    $nit_report = $nit;
                    $dui_report = $isSame ? '' : $dui;
                } else { // Natural
                    $dui_report = $dui;
                    $nit_report = $isSame ? '' : $nit;
                }

//                $neto=0;
//                $iva=0;
//                $total=0;
//                $retention=0;


                if($sale->sale_status=="Anulado"){
                    $sale->sale_status="Invalidado";
                    $sale->neto = 0;
                    $sale->iva = 0;
                    $sale->total = 0;
                    $sale->retention = 0;
                }


                return [
                    'id_venta' => $sale->id,
                    'fecha' =>date('d/m/Y', strtotime($sale->fecha)),
//                    'fecha' => Carbon::parse($sale->operation_date)->format('d/m/Y'),
                    'document_type' => '4',
                    'type' => $sale->documenttype->code,
                    'sello_recepcion' => $sale->dteProcesado->selloRecibido ?? null,
                    'cod_generaicon' => $sale->dteProcesado->codigoGeneracion ?? null,
                    'num_control' => $sale->dteProcesado->num_control ?? null,//DTE
//                    'internal_number' => $sale->id,
//                    'num_inicial' => $sale->dteProcesado->num_control ?? null,
//                    'num_final' => $sale->dteProcesado->num_control ?? null,
                    'nrc' => $sale->customer->nrc ?? null,


//                    'nit' => ($nit == $dui) ? '' : (string)$nit,
//                    'dui' => (string)$dui,
                    'nit' => $nit_report,
                    'dui' => $dui_report,
                    'nombre' => $sale->customer->fullname ?? null,
                    'exentas' => $sale->exentas ?? null,
                    'no_sujetas' => $sale->no_sujetas ?? null,
//                    'venta_gravada' => $sale->venta_gravada,
                    'neto' => $sale->neto??0,
                    'iva' => $sale->iva??0,
                    'retencion_1_percetage' => $sale->retention ?? 0,
                    'isr_10_percentage' => $sale->retention ?? 0,
                    'total' => $sale->total,
//                    'dte' => $sale->is_dte ? 'enviado' : 'pendiente',
//                    'facturacion' => $sale->billingModel->name ?? null,
//                    'vendedor' => $sale->seller->name ?? null,
//                    'condicion' => $sale->salescondition->name ?? null,
                    'estado' => strtoupper($sale->sale_status),
                    'fecha_mh' => $sale->dteProcesado?->fhProcesamiento? date('d/m/Y', strtotime($sale->dteProcesado->fhProcesamiento)): null,


                ];
            });

        return $sales;
    }

    public function columnFormats(): array
    {
        return [
//            'G' => NumberFormat::FORMAT_TEXT,
//            'H' => NumberFormat::FORMAT_TEXT,
//            'I' => NumberFormat::FORMAT_TEXT,
        ];
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
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F2F2F2'],
                    ],
                ]);
            },
        ];
    }
}