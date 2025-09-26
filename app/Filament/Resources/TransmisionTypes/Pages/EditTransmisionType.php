<?php

namespace App\Filament\Resources\TransmisionTypes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\TransmisionTypes\TransmisionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmisionType extends EditRecord
{
    protected static string $resource = TransmisionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
