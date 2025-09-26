<?php

namespace App\Filament\Resources\Tributes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Tributes\TributeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTribute extends EditRecord
{
    protected static string $resource = TributeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
