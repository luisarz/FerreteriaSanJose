<?php

namespace App\Http\Controllers;

use App\Exports\inventoryExport;
use App\Exports\InventoryMovimentExport;
use App\Exports\SalesExportFac;
use App\Filament\Exports\InventoryExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReport extends Controller
{
    public function inventoryReportExport($update,$startDate, $endDate): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);


        return Excel::download(
            new inventoryExport($update,$startDate, $endDate),
            "Reporte de inventario-{$startDate->format('Y-m-d')}-{$endDate->format('Y-m-d')}.xlsx"
        );
    }
    public function inventoryMovimentReportExport($code,$startDate, $endDate): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
        $productCode=$code;

        return Excel::download(
            new InventoryMovimentExport($productCode, $startDate , $endDate),
            "Reporte movimiento de inventario-{$startDate->format('Y-m-d')}-{$endDate->format('Y-m-d')}.xlsx"
        );
    }
}
