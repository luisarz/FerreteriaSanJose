<?php

namespace App\Filament\Resources\Kardexes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Kardexes\KardexResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditKardex extends EditRecord
{
    protected static string $resource = KardexResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
