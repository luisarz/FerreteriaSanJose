<?php

namespace App\Filament\Resources\CustomerDocumentTypes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\CustomerDocumentTypes\CustomerDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCustomerDocumentTypes extends ListRecords
{
    protected static string $resource = CustomerDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
