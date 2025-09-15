<?php

namespace App\Filament\Resources\CreditNotePurchaseResource\Pages;

use App\Filament\Resources\CreditNotePurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCreditNotePurchases extends ListRecords
{
    protected static string $resource = CreditNotePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
