<?php

namespace App\Filament\Resources\Providers\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Providers\ProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProviders extends ListRecords
{
    protected static string $resource = ProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
