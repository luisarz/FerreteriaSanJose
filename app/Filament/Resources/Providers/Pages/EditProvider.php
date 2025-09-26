<?php

namespace App\Filament\Resources\Providers\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Providers\ProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProvider extends EditRecord
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
