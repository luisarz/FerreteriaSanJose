<?php

namespace App\Filament\Resources\Tributes\Pages;

use App\Filament\Resources\Tributes\TributeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTribute extends CreateRecord
{
    protected static string $resource = TributeResource::class;
}
