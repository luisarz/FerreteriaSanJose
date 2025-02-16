<?php

namespace App\Filament\Resources\PurchaseResource\Widgets;

use App\Models\Purchase;
use App\Models\Sale;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PurchaseWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '10s'; // Auto-refrescar cada 10 segundos

    protected function getStats(): array
    {
        $purchase_total=Purchase::sum('purchase_total');
        return [
            Stat::make('Total de Compras', $purchase_total)
                ->description('Total de compras realizadas')
                ->icon('heroicon-o-shopping-cart')
            ->chart([0,$purchase_total])
            ->color('danger')
        ];
    }
}
