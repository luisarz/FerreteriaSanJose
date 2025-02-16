<?php

namespace App\Filament\Resources\CustomerDocumentTypeResource\Pages;

use App\Filament\Resources\CustomerDocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerDocumentType extends CreateRecord
{
    protected static string $resource = CustomerDocumentTypeResource::class;
}
