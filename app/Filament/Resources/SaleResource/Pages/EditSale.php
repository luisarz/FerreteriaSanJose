<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Helpers\KardexHelper;
use App\Models\CashBox;
use App\Models\CashBoxCorrelative;
use App\Models\Customer;
use App\Models\DteTransmisionWherehouse;
use App\Models\Inventory;
use App\Models\Provider;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Service\GetCashBoxOpenedService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use http\Client;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    protected function getFormActions(): array
    {
        return [
            // Acción para finalizar la venta
            Action::make('save')
                ->label('Finalizar Venta')
                ->color('success')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Confirmación')
                ->modalSubheading('¿Estás seguro de que deseas Finalizar esta venta?')
                ->modalButton('Sí, Finalizar venta')
                ->action(function (Actions\EditAction $edit) {
                    if ($this->record->sale_total <= 0) {
                        Notification::make('No se puede finalizar la venta')
                            ->title('Error al finalizar venta')
                            ->body('El monto total de la venta debe ser mayor a 0')
                            ->danger()
                            ->send();

                        return;
                    }

                    $documentType = $this->data['document_type_id'];
                    if ($documentType == "") {
                        Notification::make('No se puede finalizar la venta')
                            ->title('Tipo de documento')
                            ->body('No se puede finalizar la venta, selecciona el tipo de documento a emitir')
                            ->danger()
                            ->send();
                        return;
                    }

                    $operation_condition_id = $this->data['operation_condition_id'];
                    if ($operation_condition_id == "") {
                        Notification::make('No se puede finalizar la venta')
                            ->title('Condición de operación')
                            ->body('No se puede finalizar la venta, selecciona la condicion de la venta')
                            ->danger()
                            ->send();
                        return;
                    }

                    $payment_method_id = $this->data['payment_method_id'];

                    if ($payment_method_id == "") {
                        Notification::make('No se puede finalizar la venta')
                            ->title('Forma de pago')
                            ->body('No se puede finalizar la venta, selecciona la forma de pago')
                            ->danger()
                            ->send();
                        return;
                    }


                    $openedCashBox = (new GetCashBoxOpenedService())->getOpenCashBoxId(false);
                    if (!$openedCashBox) {
                        Notification::make('No se puede finalizar la venta')
                            ->title('Caja cerrada')
                            ->body('No se puede finalizar la venta porque no hay caja abierta')
                            ->danger()
                            ->send();
                        return;
                    }


                    if ($this->record->sale_total <= 0) {
                        Notification::make('No se puede finalizar la venta')
                            ->title('Error al finalizar venta')
                            ->body('El monto total de la venta debe ser mayor a 0')
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($this->data['operation_condition_id'] == 1) {
                        $sale_total = isset($this->data['sale_total'])
                            ? doubleval($this->data['sale_total'])
                            : 0.0;
                        $cash = isset($this->data['cash'])
                            ? doubleval($this->data['cash'])
                            : 0.0;

                        if ($cash < $sale_total) {
                            Notification::make('No se puede finalizar la venta')
                                ->title('Error al finalizar venta')
                                ->body('El monto en efectivo es menor al total de la venta')
                                ->danger()
                                ->send();
                            return;
                        }
                    }

                    //Obtenre modeloFacturacion
                    //Obtener tipo de transmision
                    $wherehouse_id = $this->record->wherehouse_id;
                    $modeloFacturacion = DteTransmisionWherehouse::where('wherehouse', $wherehouse_id)->first();
                    $billing_model = $modeloFacturacion->billing_model;
                    $transmision_type = $modeloFacturacion->transmision_type;
                    if($billing_model==null || $billing_model==""){
                        Notification::make('No se puede finalizar la venta')
                            ->title('Error al finalizar venta')
                            ->body('No se puede finalizar la venta, Sin definir el modelo de facturacion')
                            ->danger()
                            ->send();
                        return;
                    }
                    if($transmision_type==null || $transmision_type==""){
                        Notification::make('No se puede finalizar la venta')
                            ->title('Error al finalizar venta')
                            ->body('No se puede finalizar la venta, Sin definir el tipo de transmision')
                            ->danger()
                            ->send();
                        return;
                    }

                    $id_sale = $this->record->id; // Obtener el registro de la compra
                    $sale = Sale::with('documenttype', 'customer', 'customer.country')->find($id_sale);
                    $sale->document_type_id = $documentType;
                    $sale->payment_method_id = $payment_method_id;
                    $sale->operation_condition_id = $operation_condition_id;
                    $sale->billing_model = $billing_model;
                    $sale->transmision_type = $transmision_type;
                    $sale->save();

//                    $document_type_id =$this->record->document_type_id;
                    $document_internal_number_new = 0;
                    $lastIssuedDocument = CashBoxCorrelative::where('document_type_id', $documentType)->first();
                    if ($lastIssuedDocument) {
                        $document_internal_number_new = $lastIssuedDocument->current_number + 1;
                    }


                    $salesItem = SaleItem::where('sale_id', $sale->id)->get();
                    $client = $sale->customer;
                    $documnetType = $sale->documenttype->name ?? 'S/N';
                    $entity = $client->name . ' ' . $client->last_name;
                    $pais = $client->country->name ?? 'Salvadoreña';
                    foreach ($salesItem as $item) {
                        $inventory = Inventory::find($item->inventory_id);

                        // Verifica si el inventario existe
                        if (!$inventory) {
                            \Log::error("Inventario no encontrado para el item de compra: {$item->id}");
                            continue; // Si no se encuentra el inventario, continua con el siguiente item
                        }

                        // Actualiza el stock del inventario
                        $newStock = $inventory->stock - $item->quantity;
                        $inventory->update(['stock' => $newStock]);

                        // Crear el Kardex
                        $kardex = KardexHelper::createKardexFromInventory(
                            $inventory->branch_id, // Se pasa solo el valor de branch_id (entero)
                            $sale->created_at, // date
                            'Venta', // operation_type
                            $sale->id, // operation_id
                            $item->id, // operation_detail_id
                            $documnetType, // document_type
                            $document_internal_number_new, // document_number
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
                            0 // purchase_price
                        );

                        // Verifica si la creación del Kardex fue exitosa
                        if (!$kardex) {
                            \Log::error("Error al crear Kardex para el item de compra: {$item->id}");
                        }
                    }


                    $sale->update([
                        'cashbox_open_id' => $openedCashBox,
                        'is_invoiced' => true,
                        'sales_payment_status' => 'Pagada',
                        'sale_status' => 'Facturada',
                        'document_internal_number' => $document_internal_number_new
                    ]);

                    //obtener id de la caja y buscar la caja
                    $idCajaAbierta = (new GetCashBoxOpenedService())->getOpenCashBoxId(true);
                    $correlativo = CashBoxCorrelative::where('cash_box_id', $idCajaAbierta)->where('document_type_id', $this->record->document_type_id)->first();
                    $correlativo->current_number = $document_internal_number_new;
                    $correlativo->save();
                    Notification::make()
                        ->title('Venta Finalizada')
                        ->body('Venta finalizada con éxito. # Comprobante **' . $document_internal_number_new . '**')
                        ->success()
                        ->send();

                    // Redirigir después de completar el proceso
                    $this->redirect(static::getResource()::getUrl('index'));
                }),


            // Acción para cancelar la venta
            Action::make('cancelSale')
                ->label('Cancelar venta')
                ->icon('heroicon-o-no-symbol')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Confirmación')
                ->modalSubheading('¿Estás seguro de que deseas cancelar esta venta? Esta acción no se puede deshacer.')
                ->modalButton('Sí, cancelar venta')
                ->action(function (Actions\DeleteAction $delete) {
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