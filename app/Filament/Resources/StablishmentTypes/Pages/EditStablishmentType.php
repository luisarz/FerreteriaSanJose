<?php

namespace App\Filament\Resources\StablishmentTypes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\StablishmentTypes\StablishmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStablishmentType extends EditRecord
{
    protected static string $resource = StablishmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
