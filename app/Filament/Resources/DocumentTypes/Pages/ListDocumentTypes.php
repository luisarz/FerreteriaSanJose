<?php

namespace App\Filament\Resources\DocumentTypes\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\DocumentTypes\DocumentTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentTypes extends ListRecords
{
    protected static string $resource = DocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
