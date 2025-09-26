<?php

namespace App\Filament\Resources\Distritos\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Distritos\DistritoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDistritos extends ListRecords
{
    protected static string $resource = DistritoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
