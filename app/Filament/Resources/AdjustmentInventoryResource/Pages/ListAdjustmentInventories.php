<?php

namespace App\Filament\Resources\AdjustmentInventoryResource\Pages;

use App\Filament\Resources\AdjustmentInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdjustmentInventories extends ListRecords
{
    protected static string $resource = AdjustmentInventoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
