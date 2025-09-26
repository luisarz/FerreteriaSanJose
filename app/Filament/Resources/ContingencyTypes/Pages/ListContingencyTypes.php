<?php

namespace App\Filament\Resources\ContingencyTypes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\ContingencyTypes\ContingencyTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContingencyTypes extends ListRecords
{
    protected static string $resource = ContingencyTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
