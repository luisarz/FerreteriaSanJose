<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Exports\inventoryExport;
use App\Exports\InventoryMovimentExport;
use App\Exports\SalesExportFac;
use App\Filament\Exports\InventoryExporter;
use App\Models\Inventory;
use App\Models\Category;
use App\Models\Marca;
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

        $categoryId = $request->get('category_id');
        $marcaId = $request->get('marca_id');
        $branchId = $request->get('branch_id');

        // Validar que al menos un filtro esté presente
        if (empty($categoryId) && empty($marcaId)) {
            return redirect()->back()->with('error', 'Debe seleccionar al menos una categoría o marca.');
        }

        // Query optimizada con select específico y joins
        $query = Inventory::query()
            ->select([
                'inventories.id',
                'inventories.product_id',
                'inventories.branch_id',
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

        // Filtrar por categoría
        if ($categoryId) {
            $query->where('products.category_id', $categoryId);
        }

        // Filtrar por marca
        if ($marcaId) {
            $query->where('products.marca_id', $marcaId);
        }

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

        // Obtener información de filtros para el título
        $categoryName = $categoryId ? Category::find($categoryId)?->name : null;
        $marcaName = $marcaId ? Marca::find($marcaId)?->nombre : null;

        $data = [
            'groupedInventories' => $groupedInventories,
            'categoryName' => $categoryName,
            'marcaName' => $marcaName,
            'fecha' => now()->format('d/m/Y H:i'),
            'totalProductos' => $totalProductos,
        ];

        $pdf = Pdf::loadView('reports.inventory-counting', $data);
        $pdf->setPaper('letter', 'portrait');

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
