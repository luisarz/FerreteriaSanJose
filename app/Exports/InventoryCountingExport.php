<?php

namespace App\Exports;

use App\Models\Inventory;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryCountingExport implements FromView, ShouldAutoSize, WithStyles, WithTitle
{
    protected int $branchId;
    protected string $branchName;
    protected ?string $productName;

    public function __construct(int $branchId, string $branchName, ?string $productName = null)
    {
        $this->branchId = $branchId;
        $this->branchName = $branchName;
        $this->productName = $productName;
    }

    public function view(): View
    {
        $query = Inventory::query()
            ->select([
                'inventories.id',
                'inventories.product_id',
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
            ->where('inventories.branch_id', $this->branchId)
            ->where('inventories.is_active', true)
            ->where('products.is_active', true)
            ->whereNull('products.deleted_at')
            ->whereNull('inventories.deleted_at');

        if (!empty($this->productName)) {
            $query->where('products.name', 'LIKE', $this->productName . '%');
        }

        $query->orderBy('categories.name')
              ->orderBy('products.name');

        // Agrupar por categoría
        $inventories = $query->get();
        $groupedInventories = $inventories->groupBy('category_name');

        return view('exports.inventory-counting', [
            'groupedInventories' => $groupedInventories,
            'branchName' => $this->branchName,
            'searchTerm' => $this->productName ?: 'Todos los productos',
            'fecha' => now()->format('d/m/Y H:i'),
            'totalProductos' => $inventories->count(),
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['bold' => true, 'size' => 11]],
            3 => ['font' => ['bold' => true], 'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E85D04']
            ]],
        ];
    }

    public function title(): string
    {
        return 'Conteo Inventario';
    }
}
