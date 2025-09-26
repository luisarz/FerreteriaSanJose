<?php

namespace App\Filament\Resources\EconomicActivities\Pages;

use App\Filament\Resources\EconomicActivities\EconomicActivityResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEconomicActivity extends CreateRecord
{
    protected static string $resource = EconomicActivityResource::class;
}
