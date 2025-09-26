<?php

namespace App\Filament\Resources\Distritos\Pages;

use App\Filament\Resources\Distritos\DistritoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDistrito extends EditRecord
{
    protected static string $resource = DistritoResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\DeleteAction::make(),
        ];

    }
    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }
}
