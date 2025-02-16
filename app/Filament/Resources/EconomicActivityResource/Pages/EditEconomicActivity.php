<?php

namespace App\Filament\Resources\EconomicActivityResource\Pages;

use App\Filament\Resources\EconomicActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEconomicActivity extends EditRecord
{
    protected static string $resource = EconomicActivityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
