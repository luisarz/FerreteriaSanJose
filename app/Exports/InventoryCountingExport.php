<?php

namespace App\Exports;

use App\Models\Inventory;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Generator;

class InventoryCountingExport implements FromGenerator, WithHeadings, WithStyles, WithTitle, WithColumnWidths, WithEvents
{
    protected int $branchId;
    protected string $branchName;
    protected ?string $productName;
    protected int $rowCount = 0;

    public function __construct(int $branchId, string $branchName, ?string $productName = null)
    {
        $this->branchId = $branchId;
        $this->branchName = $branchName;
        $this->productName = $productName;
    }

    /**
     * Usar generador para liberar memoria progresivamente
     */
    public function generator(): Generator
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

        $index = 1;
        $currentCategory = null;

        // Usar cursor para no cargar todo en memoria
        foreach ($query->cursor() as $inventory) {
            // Si cambia la categoría, agregar fila de encabezado
            if ($currentCategory !== $inventory->category_name) {
                $currentCategory = $inventory->category_name;
                // Fila de categoría (se estilizará después)
                yield [
                    '>>> ' . ($currentCategory ?? 'Sin Categoría'),
                    '', '', '', '', '', ''
                ];
            }

            yield [
                $index++,
                $inventory->bar_code ?: 'COD-' . $inventory->product_id,
                $inventory->product_name . ($inventory->marca_name ? ' (' . $inventory->marca_name . ')' : ''),
                $inventory->unit_description ?? 'N/A',
                number_format($inventory->stock, 2),
                '', // Conteo 1
                '', // Conteo 2
            ];

            $this->rowCount++;

            // Liberar memoria cada 1000 registros
            if ($this->rowCount % 1000 === 0) {
                gc_collect_cycles();
            }
        }
    }

    public function headings(): array
    {
        return [
            '#',
            'CÓDIGO',
            'PRODUCTO',
            'PRES.',
            'STOCK',
            'CONTEO 1',
            'CONTEO 2',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 15,
            'C' => 50,
            'D' => 12,
            'E' => 12,
            'F' => 12,
            'G' => 12,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E85D04']
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Título del reporte
                $sheet->insertNewRowBefore(1, 2);
                $sheet->setCellValue('A1', 'HOJA DE CONTEO DE INVENTARIO');
                $sheet->setCellValue('A2', "Sucursal: {$this->branchName} | Filtro: " . ($this->productName ?: 'Todos') . " | Fecha: " . now()->format('d/m/Y H:i'));

                $sheet->mergeCells('A1:G1');
                $sheet->mergeCells('A2:G2');

                $sheet->getStyle('A1')->applyFromArray([
                    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1E6BB8']],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                $sheet->getStyle('A2')->applyFromArray([
                    'font' => ['size' => 10],
                    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                ]);

                // Estilizar filas de categoría (las que empiezan con >>>)
                $highestRow = $sheet->getHighestRow();
                for ($row = 4; $row <= $highestRow; $row++) {
                    $cellValue = $sheet->getCell('A' . $row)->getValue();
                    if (is_string($cellValue) && str_starts_with($cellValue, '>>> ')) {
                        // Es una fila de categoría
                        $sheet->setCellValue('A' . $row, str_replace('>>> ', '', $cellValue));
                        $sheet->mergeCells("A{$row}:G{$row}");
                        $sheet->getStyle("A{$row}:G{$row}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => '1E6BB8']
                            ],
                        ]);
                    }
                }

                // Congelar encabezados
                $sheet->freezePane('A4');
            },
        ];
    }

    public function title(): string
    {
        return 'Conteo Inventario';
    }
}
