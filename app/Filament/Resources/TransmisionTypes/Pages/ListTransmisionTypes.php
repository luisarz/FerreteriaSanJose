<?php

namespace App\Filament\Resources\TransmisionTypes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\TransmisionTypes\TransmisionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmisionTypes extends ListRecords
{
    protected static string $resource = TransmisionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
