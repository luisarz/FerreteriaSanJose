<?php

namespace App\Filament\Resources\Branches\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Branches\BranchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBranch extends EditRecord
{
    protected static string $resource = BranchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
