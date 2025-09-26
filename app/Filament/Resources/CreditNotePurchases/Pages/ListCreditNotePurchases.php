<?php

namespace App\Filament\Resources\CreditNotePurchases\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CreditNotePurchases\CreditNotePurchaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCreditNotePurchases extends ListRecords
{
    protected static string $resource = CreditNotePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
