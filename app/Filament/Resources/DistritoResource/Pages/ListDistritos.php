<?php

namespace App\Filament\Resources\DistritoResource\Pages;

use App\Filament\Resources\DistritoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDistritos extends ListRecords
{
    protected static string $resource = DistritoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
