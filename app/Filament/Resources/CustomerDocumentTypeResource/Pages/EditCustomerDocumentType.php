<?php

namespace App\Filament\Resources\CustomerDocumentTypeResource\Pages;

use App\Filament\Resources\CustomerDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCustomerDocumentType extends EditRecord
{
    protected static string $resource = CustomerDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
