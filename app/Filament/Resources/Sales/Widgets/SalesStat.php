<?php

namespace App\Filament\Resources\Sales\Widgets;

use App\Models\Purchase;
use App\Models\Sale;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SalesStat extends BaseWidget
{
    use InteractsWithPageFilters;
    protected ?string $pollingInterval = '10s'; // Auto-refrescar cada 10 segundos

    protected function getStats(): array
    {
        $whereHouse = $this->pageFilters['whereHouse'] ?? auth()->user()->employee->branch_id;
        $startDate = $this->pageFilters['startDate'] ?? now()->subDays(7);
        $endDate = $this->pageFilters['endDate'] ?? now();

        $sales_total = 0;

        $purchase_total =0;
        if(auth()->user()->hasRole('super_admin')){
            $sales_total = Sale::whereBetween('operation_date', [$startDate, $endDate])
                ->where('wherehouse_id',$whereHouse)
                ->sum('sale_total');

            $purchase_total = Purchase::whereBetween('purchase_date', [$startDate, $endDate])
                ->where('wherehouse_id',$whereHouse)
                ->sum('purchase_total');
            return [
                Stat::make('Total de ventas', number_format($sales_total,2,'.',','))
                    ->description('Total de ventas realizadas')
                    ->icon('heroicon-o-shopping-cart')
                    ->chart([0,$sales_total])
                    ->color('success')
                ,
                Stat::make('Total de Compras', number_format($purchase_total,2,'.',','))
                    ->description('Total de compras realizadas')
                    ->icon('heroicon-o-shopping-cart')
                    ->chart([0,number_format($purchase_total,2,'.',',')])
                    ->color('danger'),

                Stat::make('Utilidad', number_format($sales_total-$purchase_total,2,'.',','))
                    ->description('Utilidad Total')
                    ->icon('heroicon-o-currency-dollar')
                    ->chart([0,$sales_total-$purchase_total])
                    ->color('success')


            ];
        }
        return [];


    }
}
