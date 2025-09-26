<?php

namespace App\Filament\Resources\RetentionTaxes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\RetentionTaxes\RetentionTaxeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRetentionTaxe extends EditRecord
{
    protected static string $resource = RetentionTaxeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
