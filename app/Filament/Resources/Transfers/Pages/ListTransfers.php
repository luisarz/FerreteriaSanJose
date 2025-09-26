<?php

namespace App\Filament\Resources\Transfers\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Transfers\TransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransfers extends ListRecords
{
    protected static string $resource = TransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
