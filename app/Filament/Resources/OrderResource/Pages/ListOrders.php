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
        $allOrders = Sale::where('operation_type','Order')->whereIn('sale_status', ['Finalizado','Facturada','Anulado'])->count();
        $closed = Sale::withoutTrashed()->where('operation_type','Order')->whereIn('sale_status', ['Finalizado','Facturada'])->count();
        $open = Sale::withoutTrashed()->where('operation_type','Order')->whereNotIn('sale_status', ['Finalizado','Facturada','Anulado'])
            ->where('deleted_at',null)->count();
        $anuladas = Sale::withoutTrashed()->where('operation_type','Order')->whereIn('sale_status', ['Anulado'])->count();

        return [
            "Todas" => Tab::make()
                ->badge($allOrders)
                ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                    return $query->where('operation_type', "Order")
                        ->whereIn('sale_status', ['Finalizado','Facturada','Anulado','Nueva']);
                }),
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
                ->badgeColor('info')
                ->icon('heroicon-s-lock-open')
                ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                    return $query->where('operation_type', "Order")
                        ->whereIn('sale_status', ['Nueva'])
                        ->where('deleted_at', null);
//                        ->orderBy('order_number', 'desc');
                }),
            "Anuladas" => Tab::make()
                ->label('Anuladas')
                ->badge($anuladas)
                ->badgeColor('danger')
                ->iconSize('lg')
                ->icon('heroicon-o-archive-box-x-mark')
                ->modifyQueryUsing(function (\Illuminate\Database\Eloquent\Builder $query) {
                    return $query->where('operation_type', "Order")
                        ->whereIn('sale_status', ['Anulado'])
                        ->where('deleted_at', null);
//                        ->orderBy('operation_date', 'desc');
                }),


        ];
    }
}