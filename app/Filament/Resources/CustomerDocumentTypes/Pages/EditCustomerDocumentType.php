<?php

namespace App\Filament\Resources\CustomerDocumentTypes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\CustomerDocumentTypes\CustomerDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerDocumentType extends EditRecord
{
    protected static string $resource = CustomerDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
