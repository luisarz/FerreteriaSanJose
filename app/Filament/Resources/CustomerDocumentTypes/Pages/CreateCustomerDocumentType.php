<?php

namespace App\Filament\Resources\CustomerDocumentTypes\Pages;

use App\Filament\Resources\CustomerDocumentTypes\CustomerDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerDocumentType extends CreateRecord
{
    protected static string $resource = CustomerDocumentTypeResource::class;
}
