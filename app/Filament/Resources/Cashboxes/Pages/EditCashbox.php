<?php

namespace App\Filament\Resources\Cashboxes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Cashboxes\CashboxResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCashbox extends EditRecord
{
    protected static string $resource = CashboxResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    public function beforeSave()
    {
        $this->redirect(static::getResource()::getUrl('index'));
    }

}
