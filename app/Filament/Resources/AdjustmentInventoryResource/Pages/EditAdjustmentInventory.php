<?php

namespace App\Filament\Resources\AdjustmentInventoryResource\Pages;

use App\Filament\Resources\AdjustmentInventoryResource;
use App\Helpers\KardexHelper;
use App\Models\adjustmentInventory;
use App\Models\adjustmentInventoryItems;
use App\Models\CashBoxCorrelative;
use App\Models\Contingency;
use App\Models\Inventory;
use App\Models\InventoryGrouped;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Service\GetCashBoxOpenedService;
use EightyNine\FilamentPageAlerts\PageAlert;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditAdjustmentInventory extends EditRecord
{
    protected static string $resource = AdjustmentInventoryResource::class;

    protected function getFormActions(): array
    {
        return [
            // Acción para finalizar la venta
            Action::make('save')
                ->label('Finalizar Proceso')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Confirmación')
                ->modalSubheading('¿Estás seguro de que deseas Finalizar esta proceso?')
                ->modalButton('Sí, Finalizar venta')
                ->action(function (Actions\EditAction $edit) {

                    $id_sale = $this->record->id; // Obtener el registro de la compra
                    $ajusteProceso = adjustmentInventory::find($id_sale);
                    if ($ajusteProceso->monto <= 0) {
                        PageAlert::make('No se puede finalizar la venta')
                            ->title('Error al finalizar proceso')
                            ->body('El monto total del proceso debe ser mayor a 0')
                            ->danger()
                            ->send();

                        return;
                    }

                    $salesItem = adjustmentInventoryItems::where('adjustment_id', $this->record->id)->get();
                    $documnetType = $ajusteProceso->documenttype->name ?? 'S/N';
//                    $entity = $client->name??'' . ' ' . $client->last_name??'';
                    $entity = $ajusteProceso->entidad;
                    $tipoProceso = $ajusteProceso->tipo;
                    $pais = "Salvadoreña";
                    $isSalida = $tipoProceso === 'Salida';
                    foreach ($salesItem as $item) {
                        $inventory = Inventory::with('product')->find($item->inventory_id);
                        //verificar si es un producto compuesto
                        $is_grouped = $inventory->product->is_grouped;
                        $operationType = $isSalida ? 'Salida' : 'Entrada';
                        $documentNumber =  $ajusteProceso->id;

                        if ($is_grouped) {
                            $inventoriesGrouped = InventoryGrouped::with('inventoryChild.product')
                                ->where('inventory_grouped_id', $item->inventory_id)
                                ->get();

                            foreach ($inventoriesGrouped as $inventarioHijo) {
                                $child = $inventarioHijo->inventoryChild;
                                $cantidad = $inventarioHijo->cantidad ?? 0;
                                $stock = $child->stock ?? 0;
                                $cantidad = $item->cantidad;
                                $precio = $item->precio_unitario;
                                $newStock = $stock + ($isSalida ? -$cantidad : $cantidad);
                                $child->update(['stock' => $newStock]);

                                $previewStock = $isSalida
                                    ? $child->stock + $item->cantidad
                                    : $child->stock - $item->cantidad;

                                $kardex = KardexHelper::createKardexFromInventory(
                                    $child->branch_id,                      // branch_id
                                    $ajusteProceso->created_at,             // date
                                    $operationType,                         // operation_type (Entrada, Salida o Venta)
                                    $ajusteProceso->id,                     // operation_id (ID del ajuste)
                                    $item->id,                               // operation_detail_id (detalle del ajuste)
                                    $documnetType,                           // document_type (tipo de documento)
                                    $documentNumber,                         // document_number (número del documento)
                                    $entity,                                 // entity (cliente o proveedor)
                                    $pais,                                   // nationality (país)
                                    $inventarioHijo->inventory_child_id,     // inventory_id (ID del inventario afectado)
                                    $previewStock,                      // previous_stock (stock anterior)
                                    $isSalida ? 0 : $cantidad,               // stock_in (entradas)
                                    $isSalida ? $cantidad : 0,               // stock_out (salidas)
                                    $stock - $cantidad,                      // stock_actual (nuevo stock)
                                    $isSalida ? 0 : $cantidad * $precio,     // money_in (monto de entrada)
                                    $isSalida ? $cantidad * $precio : 0,     // money_out (monto de salida)
                                    $child->stock * $precio,                        // money_actual (valor actual del stock)
                                    $precio,                                 // sale_price (precio de venta)
                                    $child->cost_without_taxes ?? 0          // purchase_price (precio de compra)

                                );

                                if (!$kardex) {
                                    \Log::error("Error al crear Kardex para el item agrupado: {$item->id}");
                                }
                            }
                        } else {
                            $cantidad = $item->cantidad;
                            $stock = $inventory->stock;
                            $precio = $item->precio_unitario;
                            $newStock = $stock + ($isSalida ? -$cantidad : $cantidad);


                            $inventory->update(['stock' => $newStock]);
//                            $previewStock = $isSalida ?   $inventory->stock + $item->quantity :  $inventory->stock - $item->quantity;
//                           $pvs=0;
//                           if($isSalida){
//                               $pvs=$inventory->stock+$item->;
//                           }
                            $previewStock = $isSalida
                                ? $inventory->stock + $item->cantidad
                                : $inventory->stock - $item->cantidad;


                            $kardex = KardexHelper::createKardexFromInventory(
                                $inventory->branch_id,                      // branch_id
                                $ajusteProceso->created_at,                // date
                                $operationType,                            // operation_type
                                $ajusteProceso->id,                        // operation_id
                                $item->id,                                  // operation_detail_id
                                $documnetType,                              // document_type
                                $documentNumber,                            // document_number
                                $entity,                                    // entity
                                $pais,                                      // nationality
                                $inventory->id,                             // inventory_id
                                $previewStock,                         // previous_stock
                                $isSalida ? 0 : $cantidad,                  // stock_in
                                $isSalida ? $cantidad : 0,                  // stock_out
                                $newStock,                                  // stock_actual
                                $isSalida ? 0 : $cantidad * $precio,        // money_in
                                $isSalida ? $cantidad * $precio : 0,        // money_out
                                $newStock * $inventory->cost_without_taxes,                           // money_actual
                                $precio,                                    // sale_price
                                $inventory->cost_without_taxes                           // purchase_price (puedes cambiar si lo necesitas)
                            );

                            if (!$kardex) {
                                \Log::error("Error al crear Kardex para el item: {$item->id}");
                            }
                        }


                    }

                    $ajusteProceso->status = "FINALIZADO";
                    $ajusteProceso->save();

                    PageAlert::make()
                        ->title($tipoProceso)
                        ->body($tipoProceso . ' finalizada con éxito.')
                        ->success()
                        ->send();

                    // Redirigir después de completar el proceso
                    $this->redirect(static::getResource()::getUrl('index'));
                }),


            // Acción para cancelar la venta
            Action::make('cancelSale')
                ->label('Cancelar venta')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Confirmación')
                ->modalSubheading('¿Estás seguro de que deseas cancelar esta venta? Esta acción no se puede deshacer.')
                ->modalButton('Sí, cancelar venta')
                ->action(function (Actions\DeleteAction $delete) {
                    if ($this->record->is_dte) {
                        PageAlert::make()
                            ->title('Error al anular venta')
                            ->body('No se puede cancelar una venta con DTE.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Eliminar la venta y los elementos relacionados
                    SaleItem::where('sale_id', $this->record->id)->delete();
                    $this->record->delete();

                    Notification::make()
                        ->title('Venta cancelada')
                        ->body('La venta y sus elementos relacionados han sido eliminados con éxito.')
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }

    #[On('refreshSale')]
    public function refresh(): void
    {
    }
}
