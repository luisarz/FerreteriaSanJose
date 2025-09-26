<?php

namespace App\Filament\Resources\DestinationEnviroments\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\DestinationEnviroments\DestinationEnviromentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDestinationEnviroments extends ListRecords
{
    protected static string $resource = DestinationEnviromentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
