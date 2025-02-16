<?php

namespace App\Filament\Resources\SmallCashBoxOperationResource\Pages;

use App\Filament\Resources\SmallCashBoxOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSmallCashBoxOperations extends ListRecords
{
    protected static string $resource = SmallCashBoxOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
