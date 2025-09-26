<?php

namespace App\Filament\Resources\Contingencies\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Contingencies\ContingencyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditContingency extends EditRecord
{
    protected static string $resource = ContingencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
