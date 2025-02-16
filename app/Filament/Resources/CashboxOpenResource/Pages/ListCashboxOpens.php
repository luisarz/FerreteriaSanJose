<?php

namespace App\Filament\Resources\CashboxOpenResource\Pages;

use App\Filament\Resources\CashboxOpenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashboxOpens extends ListRecords
{
    protected static string $resource = CashboxOpenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
