<?php

namespace App\Filament\Resources\ContingencyTypeResource\Pages;

use App\Filament\Resources\ContingencyTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContingencyType extends EditRecord
{
    protected static string $resource = ContingencyTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
