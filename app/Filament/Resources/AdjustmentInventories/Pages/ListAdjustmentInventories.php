<?php

namespace App\Filament\Resources\AdjustmentInventories\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\AdjustmentInventories\AdjustmentInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdjustmentInventories extends ListRecords
{
    protected static string $resource = AdjustmentInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
