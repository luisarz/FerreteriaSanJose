<?php

namespace App\Filament\Resources\SmallCashBoxOperations\Pages;

use App\Filament\Resources\SmallCashBoxOperations\SmallCashBoxOperationResource;
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
