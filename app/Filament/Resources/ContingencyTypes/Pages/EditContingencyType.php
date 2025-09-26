<?php

namespace App\Filament\Resources\ContingencyTypes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\ContingencyTypes\ContingencyTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContingencyType extends EditRecord
{
    protected static string $resource = ContingencyTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
