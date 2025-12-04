<?php

namespace App\Filament\Resources\CreditNotes\Pages;

use Filament\Actions\EditAction;
use Log;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\CreditNotes\CreditNoteResource;
use App\Helpers\KardexHelper;
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
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class EditCreditNote extends EditRecord
{
    protected static string $resource = CreditNoteResource::class;

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    protected function getFormActions(): array
    {
        return [
            // Acción para finalizar la venta
            Action::make('save')
                ->label('Finalizar NC')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Confirmación')
                ->modalSubheading('¿Estás seguro de que deseas Finalizar esta NC?')
                ->modalButton('Sí, Finalizar Nota')
                ->action(function (EditAction $edit) {
                    if ($this->record->sale_total <= 0) {
                        PageAlert::make('No se puede finalizar la venta')
                            ->title('Error al finalizar Nota')
                            ->body('El monto total de la Nota debe ser mayor a 0')
                            ->danger()
                            ->send();

                        return;
                    }
                    $salePayment_status = 'Pagada';
                    $status_sale_credit = 0;

                    $documentType = $this->data['document_type_id'];
                    if ($documentType == "") {
                        PageAlert::make('No se puede finalizar la venta')
                            ->title('Tipo de documento')
                            ->body('No se puede finalizar la venta, selecciona el tipo de documento a emitir')
                            ->danger()
                            ->send();
                        return;
                    }


//                    $openedCashBox = (new GetCashBoxOpenedService())->getOpenCashBox(false);
//                    if (!$openedCashBox) {
//                        PageAlert::make('No se puede finalizar la venta')
//                            ->title('Caja cerrada')
//                            ->body('No se puede finalizar la NOTA porque no hay caja abierta')
//                            ->danger()
//                            ->send();
//                        return;
//                    }


                    //Obtener tipo de transmision
                    $wherehouse_id = $this->record->wherehouse_id;
                    $exiteContingencia = Contingency::where('warehouse_id', $wherehouse_id)
                        ->where('is_close', 0)->first();
                    $billing_model = 1;
                    $transmision_type = 1;
                    if ($exiteContingencia) {
                        $exiteContingencia = $exiteContingencia->uuid_hacienda;
                        $transmision_type = 2;
                        $billing_model = 2;
                    }

                    $id_sale = $this->record->id; // Obtener el registro de la compra
                    $sale = Sale::with('documenttype', 'customer', 'customer.country')->find($id_sale);
                    $sale->document_type_id = $documentType;
                    $sale->billing_model = $billing_model;
                    $sale->transmision_type = $transmision_type;
                    $sale->save();
                    $document_internal_number_new = 0;
//                    $lastIssuedDocument = CashBoxCorrelative::where('document_type_id', $documentType)->first();
//                    if ($lastIssuedDocument) {
//                        $document_internal_number_new = $lastIssuedDocument->current_number + 1;
//                    }


                    $salesItem = SaleItem::where('sale_id', $sale->id)->get();
                    $client = $sale->customer;
                    $documnetType = $sale->documenttype->name ?? 'S/N';
//                    $entity = $client->name??'' . ' ' . $client->last_name??'';
                    $entity = ($client->name ?? 'Varios') . ' ' . ($client->last_name ?? '');

                    $pais = $client->country->name ?? 'Salvadoreña';
                    if ($sale->document_type_id == 5) {//Toca el inventario solo si es Nota de credito
                        foreach ($salesItem as $item) {
                            $inventory = Inventory::with('product')->find($item->inventory_id);

                            // Verifica si el inventario existe
                            if (!$inventory) {
                                Log::error("Inventario no encontrado para el item de compra: {$item->id}");
                                continue; // Si no se encuentra el inventario, continua con el siguiente item
                            }
                            // Actualiza el stock del inventario

                            //verificar si es un producto compuesto
                            $is_grouped = $inventory->product->is_grouped;
                            if ($is_grouped) {
                                //si es compuesto traemos todos los inventario que lo componen
                                $inventoriesGrouped = InventoryGrouped::with('inventoryChild.product')->where('inventory_grouped_id', $item->inventory_id)->get();
                                foreach ($inventoriesGrouped as $inventarioHijo) {
//                                dd($inventoryGrouped->inventoryChild);
                                    $kardex = KardexHelper::createKardexFromInventory(
                                        $inventarioHijo->inventoryChild->branch_id, // Se pasa solo el valor de branch_id (entero)
                                        $sale->created_at, // date
                                        'Nota de Crédito', // operation_type
                                        $sale->id, // operation_id
                                        $item->id, // operation_detail_id
                                        $documnetType, // document_type
                                        $document_internal_number_new, // document_number
                                        $entity, // entity
                                        $pais, // nationality
                                        $inventarioHijo->inventory_child_id, // inventory_id
                                        $inventarioHijo->inventoryChild->stock ?? 0 - $inventarioHijo->quantity ?? 0, // previous_stock
                                        $inventarioHijo->quantity, // stock_in
                                        0, // stock_out
                                        $inventarioHijo->inventoryChild->stock ?? 0 + $inventarioHijo->quantity ?? 0, // stock_actual
                                        $inventarioHijo->quantity ?? 0 * $inventarioHijo->sale_price ?? 0, // money_in
                                        0, // money_out
                                        $inventarioHijo->inventoryChild->stock ?? 0 * $inventarioHijo->sale_price ?? 0, // money_actual
                                        $inventarioHijo->sale_price ?? 0, // sale_price
                                        $inventarioHijo->inventoryChild->cost_without_taxes ?? 0 // purchase_price
                                    );
                                    if (!$kardex) {
                                        Log::error("Error al crear Kardex para el item de compra: {$item->id}");
                                    }
                                }
                            } else {
                                $newStock = $inventory->stock + $item->quantity;
                                $inventory->update(['stock' => $newStock]);
                                $kardex = KardexHelper::createKardexFromInventory(
                                    $inventory->branch_id, // Se pasa solo el valor de branch_id (entero)
                                    $sale->created_at, // date
                                    'Nota de Crédito', // operation_type
                                    $sale->id, // operation_id
                                    $item->id, // operation_detail_id
                                    $documnetType, // document_type
                                    $document_internal_number_new, // document_number
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
                                    $inventory->cost_without_taxes // purchase_price
                                );
                                if (!$kardex) {
                                    Log::error("Error al crear Kardex para el item de compra: {$item->id}");
                                }
                            }
                            // Verifica si la creación del Kardex fue exitosa
                        }
                    }


                    $sale->update([
//                        'cashbox_open_id' => $openedCashBox,
                        'cashbox_open_id' => 0,
                        'is_invoiced' => true,
                        'sales_payment_status' => $salePayment_status,
                        'sale_status' => 'Facturada',
                        'status_sale_credit' => $status_sale_credit,
                        'operation_date' => $this->data['operation_date'],
                        'document_internal_number' => 0
//                        'document_internal_number' => $document_internal_number_new
                    ]);

                    //obtener id de la caja y buscar la caja
//                    $idCajaAbierta = (new GetCashBoxOpenedService())->getOpenCashBox(true);
//                    $correlativo = CashBoxCorrelative::where('cash_box_id', $idCajaAbierta)->where('document_type_id', $documentType)->first();
//                    $correlativo->current_number = $document_internal_number_new;
//                    $correlativo->save();
                    Notification::make()
                        ->title('Nota Finalizada')
                        ->body('Nota finalizada con éxito. # Comprobante **' . $document_internal_number_new . '**')
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
                ->action(function (DeleteAction $delete) {
                    if ($this->record->is_dte) {
                        Notification::make()
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
