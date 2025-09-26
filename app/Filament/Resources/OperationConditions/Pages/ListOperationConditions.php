<?php

namespace App\Filament\Resources\OperationConditions\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\OperationConditions\OperationConditionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOperationConditions extends ListRecords
{
    protected static string $resource = OperationConditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
