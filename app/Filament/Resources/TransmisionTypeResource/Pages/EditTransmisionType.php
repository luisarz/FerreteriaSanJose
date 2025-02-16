<?php

namespace App\Filament\Resources\TransmisionTypeResource\Pages;

use App\Filament\Resources\TransmisionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmisionType extends EditRecord
{
    protected static string $resource = TransmisionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
