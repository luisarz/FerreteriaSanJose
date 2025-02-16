<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    public function getTabs(): array
    {
        $allCount = Product::withTrashed()->count();
        $servicesCount = Product::withoutTrashed()->where('is_service', true)->count();
        $productsCount = Product::withoutTrashed()->where('is_service', false)->count();
        $deletedCount = Product::onlyTrashed()->count();

        return [
            "Todos" => Tab::make()
                ->badge($allCount),
            "Servicios" => Tab::make()
                ->badge($servicesCount)
                ->label('')
                ->badgeColor('primary')
                ->icon('heroicon-o-wrench-screwdriver')
                ->modifyQueryUsing(fn (Builder $query) => $query->withTrashed()->where('is_service', true)),
            "Productos" => Tab::make()
                ->label('')
                ->badge($productsCount)
                ->badgeColor('primary')
                ->icon('heroicon-o-building-storefront')
                ->modifyQueryUsing(fn (Builder $query) => $query->withTrashed()->where('is_service', false)),
            "Eliminados" => Tab::make()
                ->badge($deletedCount)
                ->label('')
                ->badgeColor('danger')
                ->icon('heroicon-m-trash')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed()),
        ];
    }

    public function setPage($page, $pageName = 'page'): void
    {
        parent::setPage($page, $pageName);

        $this->dispatch('scroll-to-top');
    }

}
