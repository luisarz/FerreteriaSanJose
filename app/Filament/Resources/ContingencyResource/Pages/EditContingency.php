<?php

namespace App\Filament\Resources\ContingencyResource\Pages;

use App\Filament\Resources\ContingencyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContingency extends EditRecord
{
    protected static string $resource = ContingencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
