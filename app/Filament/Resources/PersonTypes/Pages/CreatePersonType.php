<?php

namespace App\Filament\Resources\PersonTypes\Pages;

use App\Filament\Resources\PersonTypes\PersonTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePersonType extends CreateRecord
{
    protected static string $resource = PersonTypeResource::class;
}
