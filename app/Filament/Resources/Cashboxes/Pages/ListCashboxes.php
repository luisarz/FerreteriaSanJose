<?php

namespace App\Filament\Resources\Cashboxes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Cashboxes\CashboxResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashboxes extends ListRecords
{
    protected static string $resource = CashboxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
