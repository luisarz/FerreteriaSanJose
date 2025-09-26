<?php

namespace App\Filament\Resources\PersonTypes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\PersonTypes\PersonTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPersonTypes extends ListRecords
{
    protected static string $resource = PersonTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
