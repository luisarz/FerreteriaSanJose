<?php

namespace App\Filament\Resources\BillingModels\Pages;

use App\Filament\Resources\BillingModels\BillingModelResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBillingModel extends CreateRecord
{
    protected static string $resource = BillingModelResource::class;
}
