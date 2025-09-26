<?php

namespace App\Filament\Resources\PersonTypes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\PersonTypes\PersonTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPersonType extends EditRecord
{
    protected static string $resource = PersonTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
