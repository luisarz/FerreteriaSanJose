<?php

namespace App\Filament\Resources\Sales\Pages;

use Filament\Actions\EditAction;
use Log;
use Filament\Actions\DeleteAction;
use App\Filament\Resources\Sales\SaleResource;
use App\Helpers\KardexHelper;
use App\Models\CashBox;
use App\Models\CashBoxCorrelative;
use App\Models\Contingency;
use App\Models\Customer;
use App\Models\DteTransmisionWherehouse;
use App\Models\Inventory;
use App\Models\InventoryGrouped;
use App\Models\Provider;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Service\GetCashBoxOpenedService;
use EightyNine\FilamentPageAlerts\PageAlert;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Exception;

class EditSale extends EditRecord
{
    protected static string $resource = SaleResource::class;
    public string $codigoCancelacion;

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function mount(...$params): void
    {
        parent::mount(...$params);
        $this->codigoCancelacion = Str::upper(Str::random(4));
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
                ->action(function (EditAction $edit) {
                    try {
                        DB::beginTransaction();

                        if ($this->record->sale_total <= 0) {
                            Notification::make('No se puede finalizar la venta')
                                ->title('Error al finalizar venta')
                                ->body('El monto total de la venta debe ser mayor a 0')
                                ->danger()
                                ->send();

                            return;
                        }
                        $salePayment_status = 'Pagada';
                        $status_sale_credit = 0;

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


                        $openedCashBox = (new GetCashBoxOpenedService())->getOpenCashBox();
                        if (!$openedCashBox['status']) {
                            Notification::make('No se puede finalizar la venta')
                                ->title('Caja cerrada')
                                ->body('No se puede finalizar la venta porque no hay caja abierta')
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


                        } else {
                            $status_sale_credit = 1;
                        }

                        //Obtenre modeloFacturacion
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
                        $sale->payment_method_id = $payment_method_id;
                        $sale->operation_condition_id = $operation_condition_id;
                        $sale->billing_model = $billing_model;
                        $sale->transmision_type = $transmision_type;
                        $sale->save();


                        $salesItem = SaleItem::where('sale_id', $sale->id)->get();
                        $client = $sale->customer;
                        $documnetType = $sale->documenttype->name ?? 'S/N';
//                    $entity = $client->name??'' . ' ' . $client->last_name??'';
                        $entity = ($client->name ?? 'Varios') . ' ' . ($client->last_name ?? '');

                        $pais = $client->country->name ?? 'Salvadoreña';
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
                                        'Venta', // operation_type
                                        $sale->id, // operation_id
                                        $item->id, // operation_detail_id
                                        $documnetType, // document_type
                                        $sale->id, // document_number
                                        $entity, // entity
                                        $pais, // nationality
                                        $inventarioHijo->inventory_child_id, // inventory_id
                                        $inventarioHijo->inventoryChild->stock ?? 0 + $inventarioHijo->quantity ?? 0, // previous_stock
                                        0, // stock_in
                                        $inventarioHijo->quantity, // stock_out
                                        $inventarioHijo->inventoryChild->stock ?? 0 - $inventarioHijo->quantity ?? 0, // stock_actual
                                        0, // money_in
                                        $inventarioHijo->quantity ?? 0 * $inventarioHijo->sale_price ?? 0, // money_out
                                        $inventarioHijo->inventoryChild->stock ?? 0 * $inventarioHijo->sale_price ?? 0, // money_actual
                                        $inventarioHijo->sale_price ?? 0, // sale_price
                                        $inventarioHijo->inventoryChild->cost_without_taxes ?? 0 // purchase_price
                                    );
                                }
                            } else {
                                $newStock = $inventory->stock - $item->quantity;
                                $inventory->stock = $newStock;
                                $inventory->save();
                                $kardex = KardexHelper::createKardexFromInventory(
                                    $inventory->branch_id, // Se pasa solo el valor de branch_id (entero)
                                    $sale->created_at, // date
                                    'Venta', // operation_type
                                    $sale->id, // operation_id
                                    $item->id, // operation_detail_id
                                    $documnetType, // document_type
                                    $sale->id, // document_number
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
                            }


                            // Crear el Kardex


                            // Verifica si la creación del Kardex fue exitosa
                            if (!$kardex) {
                                Log::error("Error al crear Kardex para el item de compra: {$item->id}");
                            }
                        }


                        $sale->update([
                            'is_invoiced' => true,
                            'sales_payment_status' => $salePayment_status,
                            'sale_status' => 'Facturada',
                            'status_sale_credit' => $status_sale_credit,
                            'operation_date' => $this->data['operation_date'],
//                            'document_internal_number' => $document_internal_number_new
                            'document_internal_number' => 0
                        ]);

                        //obtener id de la caja y buscar la caja
//                        $correlativo = CashBoxCorrelative::where('cash_box_id', $idCajaAbierta)->where('document_type_id', $documentType)->first();
//                        $CashBoxCOrrelativeOpen->current_number = $document_internal_number_new;
//                        $CashBoxCOrrelativeOpen->save();
                        Notification::make()
                            ->title('Venta Finalizada')
//                            ->body('Venta finalizada con éxito. # Comprobante **' . $document_internal_number_new . '**')
                            ->body('Venta finalizada con éxito. # Comprobante **0**')
                            ->success()
                            ->send();
                        // Redirigir después de completar el proceso
                        DB::commit();

                        $this->redirect(static::getResource()::getUrl('index'));
                    } catch (Exception) {
                        DB::rollBack();
                        Notification::make('No se puede finalizar la venta')
                            ->title('Error al finalizar venta')
                            ->body('Ocurrió un error al intentar finalizar la venta.')
                            ->danger()
                            ->send();
                        return;
                    }


                }),


            // Acción para cancelar la venta
            Action::make('cancelSale')
                ->label('Eliminar Venta')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Confirmación')
                ->modalSubheading("Para cancelar esta venta, escribe el siguiente código:")
                ->modalButton('Sí, cancelar venta')
                ->schema([
                    Placeholder::make('codigo_mostrado')
                        ->label('Código:')
                        ->inlineLabel(true)
                        ->content("{$this->codigoCancelacion}")
                        ->extraAttributes(['style' => 'font-weight: bold; color: #dc2626']), // rojo y negrita

                    TextInput::make('confirmacion')
                        ->label('Codigo')
                        ->required()
                        ->inlineLabel(true)
                        ->rules(["in:{$this->codigoCancelacion}"])
                        ->validationMessages([
                            'in' => 'El código ingresado no coincide.',
                        ]),
                ])
                ->action(function (DeleteAction $delete) {
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