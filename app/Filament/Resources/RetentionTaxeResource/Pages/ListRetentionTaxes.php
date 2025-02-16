<?php

namespace App\Filament\Resources\RetentionTaxeResource\Pages;

use App\Filament\Resources\RetentionTaxeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRetentionTaxes extends ListRecords
{
    protected static string $resource = RetentionTaxeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
