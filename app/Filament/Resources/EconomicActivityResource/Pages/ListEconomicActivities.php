<?php

namespace App\Filament\Resources\EconomicActivityResource\Pages;

use App\Filament\Resources\EconomicActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEconomicActivities extends ListRecords
{
    protected static string $resource = EconomicActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
