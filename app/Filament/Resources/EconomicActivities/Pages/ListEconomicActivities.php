<?php

namespace App\Filament\Resources\EconomicActivities\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\EconomicActivities\EconomicActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEconomicActivities extends ListRecords
{
    protected static string $resource = EconomicActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
