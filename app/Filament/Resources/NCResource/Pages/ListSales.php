<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Http\Controllers\DTEController;
use App\Models\CashBoxOpen;
use App\Models\Product;
use App\Models\Sale;
use EightyNine\FilamentPageAlerts\PageAlert;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconSize;
use Filament\Support\Facades\FilamentView;
use Filament\Tables\Actions\Action;
use Filament\Tables\View\TablesRenderHook;
use Illuminate\Database\Eloquent\Builder;

class ListSales extends ListRecords
{
    protected static string $resource = SaleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('LibrosIVA')
                ->label('Libros Contables')
                ->tooltip('Generar DTE')
                ->icon('heroicon-o-rocket-launch')
                ->iconSize(IconSize::Large)
                ->requiresConfirmation()
                ->modalHeading('Generar Informe de IVA')
                ->modalDescription('Complete la información para generar el informe de IVA')
                ->modalSubmitActionLabel('Sí, Generar informe')
                ->color('danger')
                ->form([
                    DatePicker::make('desde')
                        ->inlineLabel(true)
                        ->default(now()->startOfMonth())
                        ->required(),
                    DatePicker::make('hasta')
                        ->inlineLabel(true)
                        ->default(now()->endOfMonth())
                        ->required(),
                    Select::make('documentType')
                        ->default('fact')
                        ->options([
                            'fact' => 'Factura',
                            'ccf' => 'CCF',
                        ])
                        ->required(),
                    Select::make('fileType')
                        ->required()
                        ->default('Libro')
                        ->options([
                            'Libro' => 'Libro',
                            'Anexo' => 'Anexos',
                        ])
                ])->action(function ($record, array $data) {
                    $startDate = $data['desde']; // Asegurar formato correcto
                    $endDate = $data['hasta'];   // Asegurar formato correcto
                    $documentType = $data['documentType'];
                    $fileType = $data['fileType'];

                    // Construir la ruta dinámicamente
                    $ruta = '/sale/iva/'; // Base del nombre de la ruta

                    if ($fileType === 'Libro') {
                        $ruta .= 'libro/';
                    } else {
                        $ruta .= 'csv/';
                    }

                    if ($documentType === 'fact') {
                        $ruta .= 'fact';
                    } else {
                        $ruta .= 'ccf';
                    }
                    $ruta.='/' . $startDate . '/' . $endDate;

                    return \Filament\Notifications\Notification::make()
                        ->title('Reporte preparado.')
                        ->body('Haz clic aquí para ver los resultados.')
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('Ver informe')
                                ->button()
                                ->url($ruta, true) // true = abrir en nueva pestaña
                        ])
                        ->send();

                })
                ->openUrlInNewTab(),

            Actions\CreateAction::make()
                ->label('Nueva Venta')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->visible(function () {
                    $whereHouse = auth()->user()->employee->branch_id ?? null;
                    if ($whereHouse) {
                        $cashBoxOpened = CashBoxOpen::with('cashbox')
                            ->where('status', 'open')
                            ->whereHas('cashbox', function ($query) use ($whereHouse) {
                                $query->where('branch_id', $whereHouse);
                            })
                            ->first();
                        if ($cashBoxOpened) {
                            return true;
                        } else {
                            return false;

                        }

                    }


                }),
        ];
    }

    public function getTabs(): array
    {
        $allCount = Sale::withTrashed()->count();
        $send = Sale::withTrashed()->where('is_dte', 1)->count();
        $unSend = Sale::withoutTrashed()->where('is_dte', 0)->count();
        $deletedCount = Sale::onlyTrashed()->count();

        return [
            "All" => Tab::make()
                ->badge($allCount),
            "Transmitidos" => Tab::make()
                ->badge($send)
                ->label('Enviados')
                ->badgeColor('success')
                ->icon('heroicon-o-rocket-launch')
                ->modifyQueryUsing(fn(Builder $query) => $query->withTrashed()->where('is_dte', 1)),

            "Sin Transmitir" => Tab::make()
                ->label('Sin Transmisión')
                ->badge($unSend)
                ->badgeColor('danger')
                ->icon('heroicon-s-computer-desktop')
                ->modifyQueryUsing(fn(Builder $query) => $query->withTrashed()->where('is_dte', '=', 0)),

        ];
    }
}
