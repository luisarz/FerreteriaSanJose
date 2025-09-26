<?php

namespace App\Filament\Resources\JobTitles\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\JobTitles\JobTitleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJobTitle extends EditRecord
{
    protected static string $resource = JobTitleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
