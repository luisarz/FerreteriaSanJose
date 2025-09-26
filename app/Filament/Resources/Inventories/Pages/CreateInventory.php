<?php

namespace App\Filament\Resources\Inventories\Pages;

use App\Filament\Resources\Inventories\InventoryResource;
use App\Models\Inventory;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateInventory extends CreateRecord
{
    protected static string $resource = InventoryResource::class;

    protected static bool $canCreateAnother=false;
    protected function beforeCreate(): void
    {
        $product_id = $this->data['product_id'];
        $branch_id = $this->data['branch_id'];
        $inventory = Inventory::where('product_id', $product_id)->where('branch_id', $branch_id)->first();

        if ($inventory) {
            Notification::make()
                ->title('El producto ya existe en el inventario en esta sucursal')
                ->danger()
                ->send();
            $this->halt()->stop();
        }
    }
//    protected function afterSave(): void
//    {
//        dd($this->record);  // Muestra el registro después de guardarlo
//    }
}
