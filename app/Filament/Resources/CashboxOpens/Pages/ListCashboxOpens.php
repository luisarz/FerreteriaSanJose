<?php

namespace App\Filament\Resources\CashboxOpens\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CashboxOpens\CashboxOpenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCashboxOpens extends ListRecords
{
    protected static string $resource = CashboxOpenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
