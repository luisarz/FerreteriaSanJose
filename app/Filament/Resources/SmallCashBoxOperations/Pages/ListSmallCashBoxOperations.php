<?php

namespace App\Filament\Resources\SmallCashBoxOperations\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\SmallCashBoxOperations\SmallCashBoxOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSmallCashBoxOperations extends ListRecords
{
    protected static string $resource = SmallCashBoxOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
