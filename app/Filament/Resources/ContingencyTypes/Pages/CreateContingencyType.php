<?php

namespace App\Filament\Resources\ContingencyTypes\Pages;

use App\Filament\Resources\ContingencyTypes\ContingencyTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateContingencyType extends CreateRecord
{
    protected static string $resource = ContingencyTypeResource::class;
}
