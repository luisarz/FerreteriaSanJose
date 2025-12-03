<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\inventoryExport;
use App\Exports\InventoryMovimentExport;
use App\Exports\SalesExportFac;
use App\Filament\Exports\InventoryExporter;
use App\Models\Inventory;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InventoryReport extends Controller
{
    /**
     * Genera PDF para conteo de inventario
     */
    public function inventoryCountingPdf(Request $request)
    {
        // Aumentar límites para procesamiento
        set_time_limit(120);
        ini_set('memory_limit', '512M');

        $productName = $request->get('product_name');
        $branchId = $request->get('branch_id');

        // Validar que el filtro por nombre esté presente
        if (empty($productName) || strlen($productName) < 2) {
            return redirect()->back()->with('error', 'Debe ingresar al menos 2 caracteres para buscar.');
        }

        // Query optimizada con select específico y joins
        $query = Inventory::query()
            ->select([
                'inventories.id',
                'inventories.product_id',
                'inventories.branch_id',
                'inventories.stock',
                'products.name as product_name',
                'products.bar_code',
                'categories.name as category_name',
                'marcas.nombre as marca_name',
                'unit_measurements.description as unit_description'
            ])
            ->join('products', 'inventories.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('marcas', 'products.marca_id', '=', 'marcas.id')
            ->leftJoin('unit_measurements', 'products.unit_measurement_id', '=', 'unit_measurements.id')
            ->where('inventories.is_active', true)
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->whereNull('inventories.deleted_at');

        // Filtrar por sucursal si se especifica
        if ($branchId) {
            $query->where('inventories.branch_id', $branchId);
        }

        // Filtrar por nombre del producto (empieza con)
        $query->where('products.name', 'LIKE', $productName . '%');

        // Ordenar por categoría y nombre de producto
        $query->orderBy('categories.name')
              ->orderBy('products.name');

        // Obtener datos en chunks y agrupar
        $groupedInventories = collect();
        $totalProductos = 0;

        $query->chunk(500, function ($inventories) use (&$groupedInventories, &$totalProductos) {
            foreach ($inventories as $inventory) {
                $categoryName = $inventory->category_name ?? 'Sin Categoría';

                if (!$groupedInventories->has($categoryName)) {
                    $groupedInventories[$categoryName] = collect();
                }

                $groupedInventories[$categoryName]->push($inventory);
                $totalProductos++;
            }
        });

        // Ordenar las categorías alfabéticamente
        $groupedInventories = $groupedInventories->sortKeys();

        $data = [
            'groupedInventories' => $groupedInventories,
            'searchTerm' => $productName,
            'fecha' => now()->format('d/m/Y H:i'),
            'totalProductos' => $totalProductos,
        ];

        $pdf = Pdf::loadView('reports.inventory-counting', $data);
        $pdf->setPaper('letter', 'portrait');
        $pdf->setOption('margin-top', '15mm');
        $pdf->setOption('margin-bottom', '15mm');
        $pdf->setOption('margin-left', '10mm');
        $pdf->setOption('margin-right', '10mm');

        return $pdf->stream('conteo-inventario-' . now()->format('Y-m-d') . '.pdf');
    }

    public function inventoryReportExport($update,$startDate, $endDate): BinaryFileResponse
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);


        return Excel::download(
            new inventoryExport($update,$startDate, $endDate),
            "Reporte de inventario-{$startDate->format('Y-m-d')}-{$endDate->format('Y-m-d')}.xlsx"
        );
    }
    public function inventoryMovimentReportExport($code,$startDate, $endDate): BinaryFileResponse
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
