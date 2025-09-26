<?php

namespace App\Filament\Resources\DteTransmisionWherehouses\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\DteTransmisionWherehouses\DteTransmisionWherehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDteTransmisionWherehouses extends ListRecords
{
    protected static string $resource = DteTransmisionWherehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
