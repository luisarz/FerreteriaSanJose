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
        $allOrders = Sale::withoutTrashed()->where('operation_type','Order')->whereNotIn('sale_status',['Anulado'])->count();
        $closed = Sale::withoutTrashed()->where('operation_type','Order')->whereIn('sale_status',  ['Finalizado','Facturada','Anulado'])->count();
        $open = Sale::withoutTrashed()->where('operation_type','Order')->whereNotIn('sale_status', ['Finalizado','Facturada','Anulado'])->count();

        return [
            "Todas" => Tab::make()
                ->badge($allOrders),
            "Cerradas" => Tab::make()
                ->badge($closed)
                ->label('Cerradas')
                ->badgeColor('success')
                ->icon('heroicon-o-lock-closed')
                ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                    return $query->where('operation_type', "Order")
                        ->whereIn('sale_status', ['Finalizado', 'Facturada','Anulado']);
                }),


            "Abiertas" => Tab::make()
                ->label('Abiertas')
                ->badge($open)
                ->badgeColor('danger')
                ->icon('heroicon-s-lock-open')
                ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                    return $query->where('operation_type', "Order")
                        ->whereIn('sale_status', ['Nueva']);
                }),


        ];
    }
}