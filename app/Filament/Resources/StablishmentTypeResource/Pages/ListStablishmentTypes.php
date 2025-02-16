<?php

namespace App\Filament\Resources\StablishmentTypeResource\Pages;

use App\Filament\Resources\StablishmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStablishmentTypes extends ListRecords
{
    protected static string $resource = StablishmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
