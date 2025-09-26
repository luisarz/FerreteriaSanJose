<?php

namespace App\Filament\Resources\Quotes\Pages;

use Filament\Actions\CreateAction;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Sale;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotes extends ListRecords
{
    protected static string $resource = QuoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->color('success'),
        ];
    }
    public function getTabs(): array
    {
        $allOrders = Sale::withoutTrashed()->where('operation_type','Quote')->whereNotIn('sale_status',['Anulado'])->count();
        $closed = Sale::withoutTrashed()->where('operation_type','Quote')->whereIn('sale_status',  ['Finalizado','Facturada','Anulado'])->count();
        $open = Sale::withoutTrashed()->where('operation_type','Quote')->whereNotIn('sale_status', ['Finalizado','Facturada','Anulado'])->count();

        return [
            "Todas" => Tab::make()
                ->badge($allOrders),
            "Cerradas" => Tab::make()
                ->badge($closed)
                ->label('Cerradas')
                ->badgeColor('success')
                ->icon('heroicon-o-lock-closed')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('operation_type', "Quote")
                        ->whereIn('sale_status', ['Finalizado', 'Facturada','Anulado']);
                }),


            "Abiertas" => Tab::make()
                ->label('Abiertas')
                ->badge($open)
                ->badgeColor('danger')
                ->icon('heroicon-s-lock-open')
                ->modifyQueryUsing(function (Builder $query) {
                    return $query->where('operation_type', "Quote")
                        ->whereIn('sale_status', ['Nueva']);
                }),


        ];
    }
}