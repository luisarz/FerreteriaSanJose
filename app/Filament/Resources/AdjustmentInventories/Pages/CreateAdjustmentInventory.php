<?php

namespace App\Filament\Resources\AdjustmentInventories\Pages;

use App\Filament\Resources\AdjustmentInventories\AdjustmentInventoryResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAdjustmentInventory extends CreateRecord
{
    protected static string $resource = AdjustmentInventoryResource::class;
    protected static bool $canCreateAnother = false;
}
