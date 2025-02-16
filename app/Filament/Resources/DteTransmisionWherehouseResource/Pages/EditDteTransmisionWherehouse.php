<?php

namespace App\Filament\Resources\DteTransmisionWherehouseResource\Pages;

use App\Filament\Resources\DteTransmisionWherehouseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDteTransmisionWherehouse extends EditRecord
{
    protected static string $resource = DteTransmisionWherehouseResource::class;

    public function aftersave(): void
    {
        $this->redirect(static::getResource()::getUrl('index'));
    }
}
