<?php

namespace App\Tables\Actions;

use App\Helpers\KardexHelper;
use App\Http\Controllers\OrdenController;
use App\Models\CashBoxOpen;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Actions\Action;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\HtmlString;
use PhpParser\Node\Stmt\Label;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Service\GetCashBoxOpenedService;



class QuoteAction
{

    public static function printQuote(): Action
    {
        return Action::make('printQuote')
            ->label('')
            ->icon('heroicon-o-printer')
            ->iconSize(IconSize::Large)
            ->color('primary')
            ->url(fn($record) => route('printQuote', ['idVenta' => $record->id]))
            ->openUrlInNewTab(); // Esto asegura que se abra en una nueva pestaña
    }

    public static function billingOrden(): Action
    {
        return Action::make('billingOrder')
            ->label('Facturar')
            ->icon('heroicon-o-arrow-up-on-square-stack')
            ->iconSize(IconSize::Large)
//            ->visible(function ($record) {
//                return $record->sale_status != 'Finalizado' && $record->status != 'Anulado';
//            })
            ->visible(fn($record) => !in_array($record->sale_status, ['Finalizado','Facturada', 'Anulado']))

            ->color('primary')
            ->action(function ($record) {
                $whereHouse = auth()->user()->employee->branch_id ?? null;
                if ($whereHouse) {
                    $cashBoxOpened = CashBoxOpen::with('cashbox')
                        ->where('status', 'open')
                        ->whereHas('cashbox', function ($query) use ($whereHouse) {
                            $query->where('branch_id', $whereHouse);
                        })
                        ->first();
                    if ($cashBoxOpened) {
                        redirect()->route('billingOrder', ['idVenta' => $record->id]);

                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Error al procesar Orden')
                            ->body('No hay ninguna caja aperturada. Por favor aperturar una caja para procesar la orden.')
                            ->send();

                    }
                }
            });

    }

    public static function closeOrder(): Action
    {
        return Action::make('closeOrder')
            ->label('Cerrar')
            ->icon('heroicon-o-lock-closed')
            ->tooltip('Cerrar orden')
            ->iconSize(IconSize::Large)
            ->color('info')
            ->requiresConfirmation()
            ->form([
                Section::make('Cerrar orden')
                    ->columns(1)
                    ->schema([
                        Placeholder::make('total_order')
                            ->label('Total')
                            ->content(fn(?Sale $record) => new HtmlString('<span style="font-weight: bold; color: red; font-size: 18px;">$ ' . number_format($record->sale_total ?? 0, 2) . '</span>'))
                            ->inlineLabel()
                            ->extraAttributes(['class' => 'p-0 text-lg']),

                        Select::make('descuento')
                            ->label('Descuento')
                            ->placeholder('Descuento')
                            ->options(fn() => collect(range(0, 25))->mapWithKeys(fn($value) => [$value => "$value%"]))
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (?Sale $record, $state, callable $set) {
                                $saleTotal = $record->sale_total ?? 0;
                                $discountedTotal = $saleTotal - ($saleTotal * $state / 100);
                                $set('total_a_cancelar', number_format($discountedTotal, 2,'.',''));
                            }),

                        TextInput::make('total_a_cancelar')
                            ->label('Total a cancelar')
                            ->prefix('$')
                            ->numeric()
                            ->placeholder('Total')
                            ->required()
                            ->readOnly()
                            ->default(fn($record) => $record?->sale_total),
                    ]),
            ])
            ->visible(fn($record) => !in_array($record->sale_status, ['Finalizado','Facturada', 'Anulado']))
//            ->hidden(fn($record) => !in_array($record->sale_status, ['Facturada', 'Anulado']))
            ->modalHeading('Confirmación')
            ->modalSubheading('¿Estás seguro de que deseas cerrar esta orden? Esta acción no se puede deshacer.')
            ->action(function ($record, array $data) {
                $openedCashBox = (new GetCashBoxOpenedService())->getOpenCashBox(false);
                if (!$openedCashBox['status'] ) {
                    return Notification::make('')
                        ->title('Error al procesar Cotizacion')
                        ->body('No hay ninguna caja aperturada. Por favor aperturar una caja para procesar la cotizacion.')
                        ->danger()
                        ->send();
                }

                if ($record->sale_status === 'Finalizado') {
                    return Notification::make('errorCloseOrder')
                        ->title('Error al cerrar orden')
                        ->body('No se puede cerrar una orden ya cerrada.')
                        ->danger()
                        ->send();
                }

                if (OrderCloseKardex($record, false, '')) {
                    $saleTotal = $record->sale_total ?? 0;
                    $discountedTotal = $saleTotal - ($saleTotal * $data['descuento'] / 100);
                    $discountMoney = number_format($saleTotal - $discountedTotal, 2, '.', '');

                    $order=Sale::find($record->id);
//                    $order->cashbox_open_id = $openedCashBox;
                    $order->operation_type = 'Order';
                    $order->is_order_closed_without_invoiced = true;
                    $order->sale_status = 'Finalizado';
                    $order->discount_percentage = $data['descuento'];
                    $order->discount_money = $discountMoney;
                    $order->total_order_after_discount = $data['total_a_cancelar'];
                    $order->save();



                    return Notification::make('Orden cerrada')
                        ->title('Orden cerrada')
                        ->body('La orden ha sido cerrada correctamente.')
                        ->success()
                        ->send();
                }

                return Notification::make('Orden cerrada')
                    ->title('Orden cerrada')
                    ->body('La orden ha sido cerrada correctamente.')
                    ->success()
                    ->send();
            });
    }


    public static function cancelOrder(): Action
    {
        return Action::make('cancelOrder')
            ->label('')
            ->icon('heroicon-o-archive-box-x-mark')
            ->tooltip('Cancelar orden')
            ->iconSize(IconSize::Large)
            ->color('danger')
            ->requiresConfirmation() // Solicita confirmación antes de ejecutar la acción


            ->visible(function ($record) {
                return $record->sale_status == 'Finalizado' && $record->is_invoiced_order == false;
            })
//            ->visible(fn($record) => !in_array($record->sale_status, ['Finalizado','Facturada', 'Anulado']))

            ->modalHeading('Confirmación!!')
            ->modalSubheading('¿Estás seguro de que deseas cerrar esta orden? Esta acción no se puede deshacer.')
            ->action(function ($record) {
                //Descargar el inventario antes de procesar la orden
                // revisar que este finalizada
                if (OrderCloseKardex($record, true, 'Anulacion')) {
                    Notification::make('Orden cerrada')
                        ->title('Orden cerrada')
                        ->body('La orden ha sido cerrada correctamente')
                        ->success()
                        ->send();
                    $record->update(['operation_type' => "Order", 'is_order_closed_without_invoiced' => true, 'sale_status' => 'Anulado']);
                }
            });

    }


}