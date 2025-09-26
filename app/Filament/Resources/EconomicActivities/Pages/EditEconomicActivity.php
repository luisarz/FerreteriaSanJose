<?php

namespace App\Filament\Resources\EconomicActivities\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\EconomicActivities\EconomicActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEconomicActivity extends EditRecord
{
    protected static string $resource = EconomicActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
