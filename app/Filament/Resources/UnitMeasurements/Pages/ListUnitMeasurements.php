<?php

namespace App\Filament\Resources\UnitMeasurements\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\UnitMeasurements\UnitMeasurementResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUnitMeasurements extends ListRecords
{
    protected static string $resource = UnitMeasurementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
