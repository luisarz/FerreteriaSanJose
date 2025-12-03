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
        $categoryId = $request->get('category_id');
        $marcaId = $request->get('marca_id');
        $branchId = $request->get('branch_id');

        // Query base
        $query = Inventory::with(['product.category', 'product.marca', 'product.unitmeasurement', 'branch'])
            ->whereHas('product', function ($q) {
                $q->where('is_active', true);
            })
            ->where('is_active', true);

        // Filtrar por sucursal si se especifica
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        // Filtrar por categoría
        if ($categoryId) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', $categoryId);
            });
        }

        // Filtrar por marca
        if ($marcaId) {
            $query->whereHas('product', function ($q) use ($marcaId) {
                $q->where('marca_id', $marcaId);
            });
        }

        // Ordenar por categoría y nombre de producto
        $inventories = $query->get()->sortBy([
            ['product.category.name', 'asc'],
            ['product.name', 'asc'],
        ]);

        // Agrupar por categoría para mejor visualización
        $groupedInventories = $inventories->groupBy(function ($inventory) {
            return $inventory->product->category->name ?? 'Sin Categoría';
        });

        // Obtener información de filtros para el título
        $categoryName = $categoryId ? Category::find($categoryId)?->name : null;
        $marcaName = $marcaId ? Marca::find($marcaId)?->nombre : null;

        $data = [
            'groupedInventories' => $groupedInventories,
            'categoryName' => $categoryName,
            'marcaName' => $marcaName,
            'fecha' => now()->format('d/m/Y H:i'),
            'totalProductos' => $inventories->count(),
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
