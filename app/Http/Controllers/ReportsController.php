<?php

namespace App\Http\Controllers;

use App\Exports\SalesExportCCF;
use App\Models\Sale;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class ReportsController extends Controller
{
    public function saleReportFact($startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $sales = Sale::select(
            'id',
            'operation_date as fecha',
            'sale_total as venta_gravada',
            'net_amount as neto',
            'taxe as iva',
            'sale_total as total'

        )
            ->where('is_dte', '1')
            ->where('document_type_id', 1)
            ->whereBetween('operation_date', [$startDate, $endDate])
            ->orderBy('operation_date', 'asc')
            ->with(['dteProcesado' => function ($query) {
                $query->select('sales_invoice_id', 'num_control', 'selloRecibido', 'codigoGeneracion')
                    ->whereNotNull('selloRecibido');
            }])
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'fecha' => $sale->fecha,
                    'resolucion' => $sale->dteProcesado->num_control ?? null,
                    'serie' => $sale->dteProcesado->selloRecibido ?? null,
                    'num_inicial' => $sale->dteProcesado->codigoGeneracion ?? null,
                    'num_final' => $sale->dteProcesado->codigoGeneracion ?? null,
                    'venta_gravada' => $sale->venta_gravada,
                    'neto' => $sale->neto,
                    'iva' => $sale->iva,
                    'total' => $sale->total,
                ];
            });
//            ->groupBy(function($sale) {
//                // Agrupa por fecha (formato Y-m-d)
//                return \Carbon\Carbon::parse($sale->operation_date)->format('Y-m-d');
//            })
//            ->map(function($group) {
//                // Extrae los últimos dígitos del número de control
//                $controlNumbers = $group->pluck('dteProcesado.num_control')->map(function($numControl) {
//                    return (int) substr($numControl, -6); // Extrae los últimos 6 dígitos
//                });
//
//                return [
//                    'total' => $group->sum('sale_total'), // Suma los montos
//                    'initial_number' => $controlNumbers->min(), // Número inicial (mínimo)
//                    'final_number' => $controlNumbers->max(), // Número final (máximo)
//                ];
//            });


        return response()->json($sales);


        return Excel::download(
            new SalesExport($documentType, $startDate, $endDate),
            "{$documentType}-{$startDate->format('Y-m-d')}-{$endDate->format('Y-m-d')}.xlsx"
        );
    }

    public function saleReportCCF($startDate, $endDate)
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);


        return Excel::download(
            new SalesExportCCF(2, $startDate, $endDate),
            "CCF-{$startDate->format('d-m-Y')}-{$endDate->format('d-m-Y')}.xlsx"
        );
    }
}
