<?php

namespace App\Filament\Resources\OperationConditions\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\OperationConditions\OperationConditionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOperationCondition extends EditRecord
{
    protected static string $resource = OperationConditionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
