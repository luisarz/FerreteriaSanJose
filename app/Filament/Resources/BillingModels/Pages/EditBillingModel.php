<?php

namespace App\Filament\Resources\BillingModels\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\BillingModels\BillingModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBillingModel extends EditRecord
{
    protected static string $resource = BillingModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
