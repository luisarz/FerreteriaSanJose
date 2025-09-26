<?php

namespace App\Tables\Actions;

use Filament\Actions\Action;
use Log;
use App\Helpers\KardexHelper;
use App\Models\Inventory;
use App\Models\Transfer;
use App\Models\TransferItems;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\IconSize;
use App\Http\Controllers\DTEController;
use App\Http\Controllers\SenEmailDTEController;
use App\Models\HistoryDte;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;

class transferActions
{
    public static function recibirTransferParcial(): Action
    {
        return Action::make('recibirTransferParcial')
            ->label('')
            ->tooltip('Recibir Traslado parcial')
            ->visible(function ($record) {
                $actualWhereHouse = auth()->user()->employee->wherehouse->id;
                $origenWhereHouse = $record->wherehouse_from;
                return $actualWhereHouse !== $origenWhereHouse && $record->status_received !== "Recibido" && $record->status_send !== "Anulado";
            })
            ->icon('heroicon-o-arrow-down-on-square-stack')
            ->iconSize(IconSize::Large)
            ->requiresConfirmation()
            ->modalHeading('¿Está seguro de recibir estos productos?')
            ->color('success')
            ->modalWidth('7xl')
            ->schema([

                Repeater::make('items')
                    ->label('Productos a recibir')
                    ->schema([
                        TextInput::make('name')
                            ->label('Producto')
                            ->disabled(),
                        TextInput::make('quantity')
                            ->label('Cantidad')
                            ->disabled(),
                        TextInput::make('price')
                            ->label('Costo')
                            ->disabled(),
                        Checkbox::make('received')
                            ->default(true)
                            ->label('Recibir'),
                    ])
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false)
                    ->extraAttributes(['class' => 'border rounded-lg p-4 bg-white shadow-sm']) // Estilo general del repeater
                    ->default(function (?Transfer $record) {
                        $itemsTransfer = TransferItems::with('inventory', 'inventory.product')->where('transfer_id', $record->id)->get();
                        if ($itemsTransfer) { // Accede al registro actual
                            return $itemsTransfer->map(fn($item) => [
                                'id' => $item->id,
                                'name' => $item->inventory->product->name,
                                'quantity' => $item->quantity,
                                'price' => $item->price,
                            ])->toArray();
                        }

                        return [];
                    })
                    ->columns(4),
            ])
            ->action(function ($record, array $data, Action $action) {
                $itemsToReceive = collect($data['items'])->filter(fn($item) => $item['received']);

                if ($itemsToReceive->isEmpty()) {
                    Notification::make()
                        ->title('No se seleccionaron productos')
                        ->body('Por favor seleccione al menos un producto para recibir')
                        ->danger()
                        ->send();
                    $action->halt();
                }

                $id_transfer = $record->id; // Obtener el registro del Traslado
                $transfer = Transfer::with('wherehouseFrom', 'wherehouseTo')->find($id_transfer);
                $client = $transfer->wherehouseTo->name;
                $entity = $client;
                $pais = 'Salvadoreña';
                $documnetType = "Recepción Traslado #  " . $transfer->transfer_number;


                foreach ($itemsToReceive as $item) {
                    $idItemTransfer = $item['id'];
                    $item = TransferItems::find($idItemTransfer);
                    $inventarioTraslado = $item->inventory_id;
                    $whereHouseTo = $transfer->wherehouse_to;

                    $inventario = Inventory::where('id', $inventarioTraslado)->first();
                    $productId = $inventario->product->id;
                    $inventory = Inventory::where('product_id', $productId)->where('branch_id', $whereHouseTo)->first();
                    // Verifica si el inventario existe
                    if (!$inventory) {
                        Log::error("Inventario no encontrado para el item de compra: {$item->id}");
                        continue; // Si no se encuentra el inventario, continua con el siguiente item
                    }
                    if($item['received']) {
                    //aumentamoe el inventario de los productos recibidos
                    }else{
//                        anulamos los items
                    }

                    $newStock = $inventory->stock + $item->quantity;
                    $inventory->update(['stock' => $newStock]);
                    // Crear el Kardex
                    $kardex = KardexHelper::createKardexFromInventory(
                        $inventory->branch_id, // Se pasa solo el valor de branch_id (entero)
                        $transfer->transfer_date, // date
                        'Traslado', // operation_type
                        $transfer->id, // operation_id
                        $item->id, // operation_detail_id
                        $documnetType, // document_type
                        $transfer->transfer_number, // document_number
                        $entity, // entity
                        $pais, // nationality
                        $inventory->id, // inventory_id
                        $inventory->stock - $item->quantity, // previous_stock
                        $item->quantity, // stock_in
                        0, // stock_out
                        $newStock, // stock_actual
                        $item->quantity * $item->price, // money_in
                        0, // money_out
                        $inventory->stock * $item->price, // money_actual
                        $item->price, // sale_price
                        $item->price // purchase_price
                    );

                    if (!$kardex) {
                        Log::error("Error al crear Kardex para el item de compra: {$item->id}");
                    }


                }

                Notification::make()
                    ->title('Productos recibidos correctamente')
                    ->success()
                    ->send();
            });
    }

    public static function recibirTransferFull(): Action
    {
        return Action::make('recibirTransferFull')
            ->label('')
            ->tooltip('Recibir traslado completo')
            ->visible(function ($record) {
                $actualWhereHouse = auth()->user()->employee->wherehouse->id;
                $origenWhereHouse = $record->wherehouse_from;
                return $actualWhereHouse !== $origenWhereHouse
                    && $record->status_received !== "Recibido"
                    && $record->status_send !== "Anulado";
            })
            ->icon('heroicon-o-arrow-down-on-square')
            ->iconSize(IconSize::Large)
            ->requiresConfirmation()
            ->modalHeading('¿Está seguro de recibir El traslado por completo?')
            ->color('success')
            ->action(function ($record, array $data) {
                $id_transfer = $record->id; // Obtener el registro del Traslado
                $transfer = Transfer::with('wherehouseFrom', 'wherehouseTo')->find($id_transfer);
                $transferItem = TransferItems::where('transfer_id', $transfer->id)->get();
                $client = $transfer->wherehouseTo->name;
                $entity = $client;
                $pais = 'Salvadoreña';
                $documnetType = "Recepción Traslado #  " . $transfer->transfer_number;

                foreach ($transferItem as $item) {
                    $inventarioTraslado = $item->inventory_id;
                    $whereHouseTo = $transfer->wherehouse_to;

                    $inventario = Inventory::where('id', $inventarioTraslado)->first();
                    $productId = $inventario->product->id;
                    $inventory = Inventory::where('product_id', $productId)->where('branch_id', $whereHouseTo)->first();
                    // Verifica si el inventario existe
                    if (!$inventory) {
                        Log::error("Inventario no encontrado para el item de compra: {$item->id}");
                        continue; // Si no se encuentra el inventario, continua con el siguiente item
                    }
                    $newStock = $inventory->stock + $item->quantity;
                    $inventory->update(['stock' => $newStock]);
                    // Crear el Kardex
                    $kardex = KardexHelper::createKardexFromInventory(
                        $inventory->branch_id, // Se pasa solo el valor de branch_id (entero)
                        $transfer->transfer_date, // date
                        'Traslado', // operation_type
                        $transfer->id, // operation_id
                        $item->id, // operation_detail_id
                        $documnetType, // document_type
                        $transfer->transfer_number, // document_number
                        $entity, // entity
                        $pais, // nationality
                        $inventory->id, // inventory_id
                        $inventory->stock - $item->quantity, // previous_stock
                        $item->quantity, // stock_in
                        0, // stock_out
                        $newStock, // stock_actual
                        $item->quantity * $item->price, // money_in
                        0, // money_out
                        $inventory->stock * $item->price, // money_actual
                        $item->price, // sale_price
                        $item->price // purchase_price
                    );

                    if (!$kardex) {
                        Log::error("Error al crear Kardex para el item de compra: {$item->id}");
                    }
                }
                $transfer->status_received = 'Recibido';
                $transfer->received_date = now();
                $transfer->status_send = 'Entregado';
                $transfer->save();
                Notification::make()
                    ->title('Productos recibidos correctamente')
                    ->success()
                    ->send();
            });
    }


    public static function anularTransfer(): Action
    {
        return Action::make('anularDTE')
            ->label('')
            ->tooltip('Anular Traslado')
            ->icon('heroicon-o-archive-box-x-mark')
            ->iconSize(IconSize::Large)
            ->visible(function ($record) {
                $actualWhereHouse = auth()->user()->employee->wherehouse->id;
                $origenWhereHouse = $record->wherehouse_from;
                return $actualWhereHouse === $origenWhereHouse
                    && $record->status_received !== "Recibido"
                    && $record->status_send !== "Anulado";
            })
            ->requiresConfirmation()
            ->modalHeading('¿Está seguro de Anular el Traslado?')
            ->modalDescription('Al anular el TRASLADO no se podrá recuperar, no se podra revertir.')
            ->color('danger')
            ->schema([
                Select::make('ConfirmacionAnular')
                    ->label('Confirmar')
                    ->options(['confirmacion' => 'Estoy seguro, si Anular Traslado'])
                    ->placeholder('Seleccione una opción')
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                if ($data['ConfirmacionAnular'] === 'confirmacion') {

                    $transfer = Transfer::find($record->id);
                    $transfer->status_send = 'Anulado';
                    $transfer->status_received = 'Anulado';
                    $transfer->save();

                    TransferItems::where('transfer_id', $transfer->id)
                        ->update(['status_sent' => 0, 'status_recived' => 0]);

                    //Regresar el inventario


                    Notification::make()
                        ->title('Traslado Anulado')
                        ->body('El traslado ha sido anulado correctamente')
                        ->success()
                        ->send();
                }
            })
            ->after(function ($record) {
                $id_transfer = $record->id; // Obtener el registro del Traslado
                $transfer = Transfer::with('wherehouseFrom', 'wherehouseTo')->find($id_transfer);
                $transferItem = TransferItems::where('transfer_id', $transfer->id)->get();
                $client = $transfer->wherehouseTo->name;
                $entity = $client;
                $pais = 'Salvadoreña';
                $documnetType = "Anulacion Traslado #  " . $transfer->transfer_number;

                foreach ($transferItem as $item) {
                    $inventory = Inventory::find($item->inventory_id);
                    // Verifica si el inventario existe
                    if (!$inventory) {
                        Log::error("Inventario no encontrado para el item de compra: {$item->id}");
                        continue; // Si no se encuentra el inventario, continua con el siguiente item
                    }
                    $newStock = $inventory->stock - $item->quantity;
                    $inventory->update(['stock' => $newStock]);
                    // Crear el Kardex
                    $kardex = KardexHelper::createKardexFromInventory(
                        $inventory->branch_id, // Se pasa solo el valor de branch_id (entero)
                        $transfer->transfer_date, // date
                        'Traslado', // operation_type
                        $transfer->id, // operation_id
                        $item->id, // operation_detail_id
                        $documnetType, // document_type
                        $transfer->transfer_number, // document_number
                        $entity, // entity
                        $pais, // nationality
                        $inventory->id, // inventory_id
                        $inventory->stock + $item->quantity, // previous_stock
                        0, // stock_in
                        $item->quantity, // stock_out
                        $newStock, // stock_actual
                        0, // money_in
                        $item->quantity * $item->price, // money_out
                        $inventory->stock * $item->price, // money_actual
                        $item->price, // sale_price
                        $item->price // purchase_price
                    );

                    if (!$kardex) {
                        Log::error("Error al crear Kardex para el item de compra: {$item->id}");
                    }
                }
            });
    }


    public static function printTransfer(): Action
    {
        return Action::make('pdf')
            ->label('')
            ->icon('heroicon-o-printer')
            ->tooltip('Imprimir Traslado')
            ->iconSize(IconSize::Large)
            ->color('default')
            ->action(function ($record) {
                return redirect()->route('printTransfer', ['idTransfer' => $record->id]);
            });
    }
}
