<?php

namespace App\Filament\Resources\PersonTypeResource\Pages;

use App\Filament\Resources\PersonTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePersonType extends CreateRecord
{
    protected static string $resource = PersonTypeResource::class;
}
