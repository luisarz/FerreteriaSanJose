<?php

namespace App\Tables\Actions;

use Filament\Actions\Action;
use App\Filament\Resources\DteTransmisionWherehouses\DteTransmisionWherehouseResource;
use App\Models\Branch;
use App\Models\DteTransmisionWherehouse;
use EightyNine\FilamentPageAlerts\PageAlert;
use Filament\Forms\Components\Select;
use Filament\Support\Enums\IconSize;
use App\Http\Controllers\DTEController;
use App\Http\Controllers\SenEmailDTEController;
use App\Models\HistoryDte;
use Filament\Notifications\Notification;

class creditNotesActions
{
    public static function generarDTE(): Action
    {
        return Action::make('dte')
            ->label('')
            ->tooltip('Generar DTE')
            ->visible(fn($record) => !$record->is_dte)
            ->icon('heroicon-o-rocket-launch')
            ->iconSize(IconSize::Large)
            ->requiresConfirmation()
            ->modalHeading('¿Está seguro de generar el DTE?')
            ->color('danger')
            ->schema([
                Select::make('confirmacion')
                    ->label('Enviar a Hacienda')
                    ->options(['si' => 'Sí, deseo enviar', 'no' => 'No, no enviar'])
                    ->required(),
            ])
            ->action(function ($record, array $data) {


                if ($data['confirmacion'] === 'si') {
                    $dteController = new DTEController();
                    $resultado = $dteController->generarDTE($record->id);
                    if ($resultado['estado'] === 'EXITO') {
                        PageAlert::make()
                            ->title('Envío Exitoso')
                            ->success()
                            ->send();
                        if ($data['confirmacion'] === 'si') {
                            self::imprimirDTE()->action($record);
                            self::enviarEmailDTE()->action($record);
                        }
                    } else {
                        PageAlert::make()
                            ->title($resultado['estado'])
                            ->danger()
                            ->body($resultado["mensaje"])
                            ->send();
                    }
                } else {
                    PageAlert::make()
                        ->title('Se canceló el envío')
                        ->warning()
                        ->send();
                }
            });
    }

    public static function anularDTE(): Action
    {
        return Action::make('anularDTE')
            ->label('')
            ->tooltip('Anular DTE')
            ->icon('heroicon-o-shield-exclamation')
            ->iconSize(IconSize::Large)
            ->visible(fn($record) => $record->is_dte && $record->sale_status != 'Anulado')
            ->requiresConfirmation()
            ->modalHeading('¿Está seguro de Anular el DTE?')
            ->modalDescription('Al anular el DTE no se podrá recuperar')
            ->color('danger')
            ->schema([
                Select::make('ConfirmacionAnular')
                    ->label('Confirmar')
                    ->options(['confirmacion' => 'Estoy seguro, si Anular'])
                    ->placeholder('Seleccione una opción')
                    ->required(),
            ])
            ->action(function ($record, array $data) {
                if ($data['ConfirmacionAnular'] === 'confirmacion') {
                    $dteController = new DTEController();
                    $resultado = $dteController->anularDTE($record->id);
                    if ($resultado['estado'] === 'EXITO') {
                        PageAlert::make()
                            ->title('Anulación Exitosa')
                            ->success()
                            ->send();
                    } else {
                        PageAlert   ::make()
                            ->title('Fallo en anulación')
                            ->danger()
                            ->body($resultado["mensaje"])
                            ->send();
                    }
                }
            });
    }

    public static function historialDTE(): Action
    {
        return Action::make('Historial')
            ->label('')
            ->icon('heroicon-o-finger-print')
            ->tooltip('Bitácora DTE')
            ->iconSize(IconSize::Large)
            ->color('primary')
            ->modalHeading('Bitácora procesos DTE')
            ->modalContent(function ($record) {
                $historial = HistoryDte::where('sales_invoice_id', $record->id)->get();

                return view('DTE.historial-dte', [
                    'record' => $record,
                    'historial' => $historial,
                ]);
            })
            ->modalDescription('Historial de envío de DTEs')
            ->modalWidth('7xl');
    }

    public static function enviarEmailDTE(): Action
    {
        return Action::make('send')
            ->label('')
            ->icon('heroicon-o-envelope')
            ->iconSize(IconSize::Large)
            ->tooltip('Enviar DTE')
            ->visible(fn($record) => $record->is_dte && $record->sale_status != 'Anulado')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('¿Está seguro de enviar el DTE?')
            ->modalDescription('Al enviar el DTE, se enviará al correo del cliente!')
            ->action(function ($record) {
                $responseSendEmail = new SenEmailDTEController();
                $response = $responseSendEmail->SenEmailDTEController($record->id);
                $responseData = $response->getData(true);
                if ($responseData['status']) {
                    Notification::make()
                        ->title('Envío Exitoso')
                        ->body($responseData['message'])
                        ->success()
                        ->send();
                } else {
                    Notification::make()
                        ->title('Fallo en envío')
                        ->body($responseData['message'])
                        ->danger()
                        ->send();
                }
            });
    }


    public static function imprimirDTE(): Action
    {
        return Action::make('pdf')
            ->label('') // Etiqueta vacía, si deseas cambiarla, agrega un texto
            ->icon('heroicon-o-printer')
            ->iconSize(IconSize::Large)
            ->color('primary')
            ->visible(fn($record) => $record->is_dte) // Esto asegura que solo se muestre si el registro tiene un DTE
//

            ->url(function ($record) {
                $idSucursal = auth()->user()->employee->branch_id;

                $print = DteTransmisionWherehouse::where('wherehouse',$idSucursal)->first();
                $ruta = $print->printer_type == 1 ? 'printDTETicket' : 'printDTEPdf';
                    return route($ruta, ['idVenta' => isset($record) ? ($record->generationCode ?? 'SN') : 'SN']);



            })

            ->openUrlInNewTab(); // Esto asegura que se abra en una nueva pestaña


    }
}
