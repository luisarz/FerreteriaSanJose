<?php

namespace App\Filament\Resources\PersonTypeResource\Pages;

use App\Filament\Resources\PersonTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPersonType extends EditRecord
{
    protected static string $resource = PersonTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
