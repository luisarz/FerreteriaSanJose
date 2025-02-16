<?php

namespace App\Filament\Resources\DestinationEnviromentResource\Pages;

use App\Filament\Resources\DestinationEnviromentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDestinationEnviroments extends ListRecords
{
    protected static string $resource = DestinationEnviromentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
