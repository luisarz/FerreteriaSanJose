<?php

namespace App\Filament\Resources\TributeResource\Pages;

use App\Filament\Resources\TributeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTribute extends EditRecord
{
    protected static string $resource = TributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
