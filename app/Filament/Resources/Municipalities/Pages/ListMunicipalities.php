<?php

namespace App\Filament\Resources\Municipalities\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Municipalities\MunicipalityResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMunicipalities extends ListRecords
{
    protected static string $resource = MunicipalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
