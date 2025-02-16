<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\CashBoxOpen;
use App\Models\Product;
use App\Models\Sale;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Database\Eloquent\Builder;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Venta')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->visible(function () {
                    $whereHouse = auth()->user()->employee->branch_id ?? null;
                    if ($whereHouse) {
                        $cashBoxOpened = CashBoxOpen::with('cashbox')
                            ->where('status', 'open')
                            ->whereHas('cashbox', function ($query) use ($whereHouse) {
                                $query->where('branch_id', $whereHouse);
                            })
                            ->first();
                       if($cashBoxOpened){
                           return true;
                       }else{
                           return false;

                       }

                    }


                }),
        ];
    }

    public function getTabs(): array
    {
        $allCount = Sale::withTrashed()->count();
        $send = Sale::withTrashed()->where('is_dte', 1)->count();
        $unSend = Sale::withoutTrashed()->where('is_dte', 0)->count();
        $deletedCount = Sale::onlyTrashed()->count();

        return [
            "All" => Tab::make()
                ->badge($allCount),
            "Transmitidos" => Tab::make()
                ->badge($send)
                ->label('Enviados')
                ->badgeColor('success')
                ->icon('heroicon-o-rocket-launch')
                ->modifyQueryUsing(fn (Builder  $query) => $query->withTrashed()->where('is_dte', 1)),

            "Sin Transmitir" => Tab::make()
                ->label('Sin Transmisión')
                ->badge($unSend)
                ->badgeColor('danger')
                ->icon('heroicon-s-computer-desktop')
                ->modifyQueryUsing(fn (Builder $query) => $query->withTrashed()->where('is_dte','=', 0)),

        ];
    }
}
