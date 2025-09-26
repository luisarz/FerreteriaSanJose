<?php

namespace App\Filament\Resources\Sales\Widgets;

use App\Models\Purchase;
use App\Models\Sale;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class ChartWidgetSales extends ChartWidget
{
    protected ?string $heading = 'Comparativo -Compras y Ventas';
    use InteractsWithPageFilters;

    protected function getData(): array
    {
//        dd($this->filters['whereHouse']);
        $whereHouse = $this->pageFilters['whereHouse'];
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(7);
        $endDate = $this->pageFilters['endDate'] ?? now();

        // Obtener las ventas agrupadas por día
        $sales = Sale::whereBetween('operation_date', [$startDate, $endDate])
            ->where('wherehouse_id', $whereHouse)
            ->orderBy('operation_date', 'asc')
            ->get()
            ->groupBy(function ($sale) {
                return Carbon::parse($sale->operation_date)->toDateString();
            });

        $salesByDay = $sales->map(function ($daySales, $day) {
            return [
                'date' => date('d-m', strtotime($day)),
                'total_sales' => $daySales->sum('sale_total'),
            ];
        });

// Obtener las compras agrupadas por día
        $purchases = Purchase::whereBetween('purchase_date', [$startDate, $endDate])
            ->where('wherehouse_id', $whereHouse)
            ->orderBy('purchase_date', 'asc')
            ->get()
            ->groupBy(function ($purchase) {
                return Carbon::parse($purchase->purchase_date)->toDateString();
            });

        $purchasesByDay = $purchases->map(function ($dayPurchases, $day) {
            return [
                'date' => date('d-m', strtotime($day)),
                'total_purchases' => $dayPurchases->sum('purchase_total'),
            ];
        });

// Combinar todas las fechas únicas de ventas y compras
        $allDates = $salesByDay->pluck('date')
            ->merge($purchasesByDay->pluck('date'))
            ->unique()
            ->sortBy(function ($date) {
                return Carbon::createFromFormat('d-m', $date);
            });

// Combinar datos de ventas y compras para todas las fechas
        $combinedData = $allDates->map(function ($date) use ($salesByDay, $purchasesByDay) {
            $salesData = $salesByDay->firstWhere('date', $date)['total_sales'] ?? 0;
            $purchasesData = $purchasesByDay->firstWhere('date', $date)['total_purchases'] ?? 0;

            return [
                'date' => $date,
                'total_sales' => $salesData,
                'total_purchases' => $purchasesData,
            ];
        });

// Extraer etiquetas y datos
        $labels = $combinedData->pluck('date')->toArray();
        $salesData = $combinedData->pluck('total_sales')->toArray();
        $purchasesData = $combinedData->pluck('total_purchases')->toArray();

// Retornar datos al gráfico
        return [
            'datasets' => [
                [
                    'label' => 'Ventas realizadas por día',
                    'data' => $salesData,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.6)',
                    'borderColor' => '#9BD0F5',
                    'borderWidth' => 1,
                    'fill'=>true,
                    'tension'=>0.5
                ],
                [
                    'label' => 'Compras realizadas por día',
                    'data' => $purchasesData,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.6)',
                    'borderColor' => '#FF6384',
                    'borderWidth' => 1,
                    'fill'=>true,
                    'tension'=>0.5
                ],
            ],
            'labels' => $labels,
        ];



    }

    protected function getType(): string
    {
        return 'line';
    }
}
