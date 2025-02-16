<?php

namespace App\Filament\Resources\PersonTypeResource\Pages;

use App\Filament\Resources\PersonTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPersonTypes extends ListRecords
{
    protected static string $resource = PersonTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
