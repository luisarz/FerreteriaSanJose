<?php

namespace App\Filament\Resources\BillingModelResource\Pages;

use App\Filament\Resources\BillingModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBillingModels extends ListRecords
{
    protected static string $resource = BillingModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
