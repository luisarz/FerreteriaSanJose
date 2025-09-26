<?php

namespace App\Filament\Resources\StablishmentTypes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\StablishmentTypes\StablishmentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStablishmentTypes extends ListRecords
{
    protected static string $resource = StablishmentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
