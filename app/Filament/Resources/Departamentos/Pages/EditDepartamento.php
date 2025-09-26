<?php

namespace App\Filament\Resources\Departamentos\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Departamentos\DepartamentoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepartamento extends EditRecord
{
    protected static string $resource = DepartamentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];

    }
    protected function getRedirectUrl(): ?string
    {
        return static::getResource()::getUrl('index');
    }
}
