<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Sale;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        $allOrders = Sale::withoutTrashed()->where('is_order',1)->whereNotIn('sale_status',['Anulado'])->count();
        $closed = Sale::withoutTrashed()->where('is_order',1)->where('sale_status', 'Finalizado')->count();
        $open = Sale::withoutTrashed()->where('is_order',1)->whereNotIn('sale_status', ['Finalizado','Anulado'])->count();

        return [
            "Todas" => Tab::make()
                ->badge($allOrders),
            "Cerradas" => Tab::make()
                ->badge($closed)
                ->label('Cerradas')
                ->badgeColor('success')
                ->icon('heroicon-o-lock-closed')
                ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                    return $query->where('is_order', true)
                        ->whereIn('sale_status', ['Finalizado', 'Anulado']);
                }),

//                ->modifyQueryUsing(fn (Builder  $query) => $query->where('is_order',true)->where('sale_status', 'Finalizado')),

            "Abiertas" => Tab::make()
                ->label('Abiertas')
                ->badge($open)
                ->badgeColor('danger')
                ->icon('heroicon-s-lock-open')
                ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                    return $query->where('is_order', true)
                        ->whereNotIn('sale_status', ['Finalizado','Anulado']);
                }),


        ];
    }
}
