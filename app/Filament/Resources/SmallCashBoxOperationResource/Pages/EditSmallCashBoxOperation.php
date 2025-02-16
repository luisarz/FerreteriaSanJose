<?php

namespace App\Filament\Resources\SmallCashBoxOperationResource\Pages;

use App\Filament\Resources\SmallCashBoxOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSmallCashBoxOperation extends EditRecord
{
    protected static string $resource = SmallCashBoxOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
//            Actions\DeleteAction::make(),
        ];
    }
}
