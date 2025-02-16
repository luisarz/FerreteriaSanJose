<?php

namespace App\Traits;

use Filament\Resources\Pages\EditRecord;

trait RedirectAfterEdit
{
    protected function afterSave(): void
    {
// Redirigir al listado después de guardar
        $this->redirect($this->getResource()::getUrl('index'));
    }
}

