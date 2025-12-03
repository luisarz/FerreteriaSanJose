<?php

namespace App\Filament\Resources\Inventories\Pages;

use App\Models\Branch;
use Auth;
use Filament\Notifications\Notification;
use App\Filament\Resources\Inventories\InventoryResource;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Actions\Action;
// use pxlrbt\FilamentExcel\Columns\Column; // No compatible con Filament 4
// use pxlrbt\FilamentExcel\Exports\ExcelExport;
// use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;

class ListInventories extends ListRecords
{
    protected static string $resource = InventoryResource::class;
    protected static ?string $navigationLabel = "Inventarios";

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('counting_pdf')
                ->label('Hoja de Conteo')
                ->tooltip('Generar PDF para conteo de inventario')
                ->icon('heroicon-o-clipboard-document-list')
                ->iconSize(IconSize::Large)
                ->color('info')
                ->modalHeading('Generar Hoja de Conteo de Inventario')
                ->modalDescription('Seleccione la sucursal. El filtro por nombre es opcional.')
                ->modalSubmitActionLabel('Generar PDF')
                ->schema([
                    Select::make('branch_id')
                        ->label('Sucursal')
                        ->options(fn () => Branch::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->default(fn () => Auth::user()->employee->wherehouse->id ?? null)
                        ->required()
                        ->searchable()
                        ->preload()
                        ->helperText('Seleccione la sucursal para el conteo'),
                    TextInput::make('product_name')
                        ->label('Nombre del producto (opcional)')
                        ->placeholder('Ej: TORNILLO, TUERCA, CABLE... o dejar vacío para todos')
                        ->helperText('Deje vacío para listar todos los productos de la sucursal'),
                ])->action(function (array $data) {
                    $branchId = $data['branch_id'] ?? null;
                    $productName = trim($data['product_name'] ?? '');

                    if (empty($branchId)) {
                        return Notification::make()
                            ->title('Sucursal requerida')
                            ->body('Debe seleccionar una sucursal.')
                            ->danger()
                            ->send();
                    }

                    $params = ['branch_id' => $branchId];
                    if (!empty($productName)) {
                        $params['product_name'] = $productName;
                    }

                    $url = route('inventory.counting.pdf', $params);

                    return Notification::make()
                        ->title('PDF generado')
                        ->body('Haz clic para abrir la hoja de conteo.')
                        ->success()
                        ->actions([
                            \Filament\Actions\Action::make('Abrir PDF')
                                ->button()
                                ->url($url, true)
                        ])
                        ->send();
                }),
//            ExportAction::make()
//                ->exports([
//                    ExcelExport::make()
//                        ->fromTable()
//                        ->withFilename(fn ($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
//                        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
//                        ->withColumns([
//                            Column::make('updated_at'),
//                            Column::make('created_at'),
//                            Column::make('deleted_at'),
//                        ])
//                ]),
            Actions\Action::make('inventiry')
                ->label('Descargar Inventario')
                ->tooltip('Generar DTE')
                ->icon('heroicon-o-rocket-launch')
                ->iconSize(IconSize::Large)
                ->requiresConfirmation()
                ->modalHeading('Generar Informe de Inventario')
                ->modalDescription('Complete la información para generar el informe')
                ->modalSubmitActionLabel('Sí, Generar informe')
                ->color('danger')
                ->schema([
                    DatePicker::make('desde')
                        ->inlineLabel(true)
                        ->default(now()->startOfMonth())
                        ->required(),
                    DatePicker::make('hasta')
                        ->inlineLabel(true)
                        ->default(now()->endOfMonth())
                        ->required(),
                    Toggle::make('update')
                        ->label('Actualizar Stock')
                        ->inlineLabel(true)
                        ->helperText('Si está activado, se actualizarán los stock de inventario al generar el informe.')
                        ->default(false)
                        ->required(),

                ])->action(function ($record, array $data) {

                    $startDate = $data['desde']; // Asegurar formato correcto
                    $endDate = $data['hasta'];   // Asegurar formato correcto
                    $update = $data['update']??0;   // Asegurar formato correcto

                    // Construir la ruta dinámicamente
                    $ruta = '/inventor/report/' . $update . '/' . $startDate . '/' . $endDate; // Base del nombre de la ruta
                    return Notification::make()
                        ->title('Reporte preparado.')
                        ->body('Haz clic aquí para ver los resultados.')
                        ->actions([
                            \Filament\Actions\Action::make('Ver informe')
                                ->button()
                                ->url($ruta, true) // true = abrir en nueva pestaña
                        ])
                        ->send();

                })
                ->openUrlInNewTab(),
            Actions\Action::make('inventiry_moviment')
                ->label('Movimientos de Inventario')
                ->tooltip('Generar Informe')
                ->icon('heroicon-o-adjustments-horizontal')
                ->iconSize(IconSize::Large)
                ->requiresConfirmation()
                ->modalHeading('Generar Informe de movimientos de inventario')
                ->modalDescription('Complete la información para generar el informe')
                ->modalSubmitActionLabel('Sí, Generar informe')
                ->color('warning')
                ->schema([
                    TextInput::make('code')
                        ->required()
                        ->label('Código de producto'),
                    DatePicker::make('desde')
                        ->inlineLabel(true)
                        ->default(now()->startOfMonth())
                        ->required(),
                    DatePicker::make('hasta')
                        ->inlineLabel(true)
                        ->default(now()->endOfMonth())
                        ->required(),

                ])->action(function ($record, array $data) {
                    $startDate = $data['desde']; // Asegurar formato correcto
                    $endDate = $data['hasta'];   // Asegurar formato correcto
                    $code = $data['code'];   // Asegurar formato correcto

                    // Construir la ruta dinámicamente
                    $ruta = '/inventor/report-mov/' . $code . '/' . $startDate . '/' . $endDate; // Base del nombre de la ruta
                    return Notification::make()
                        ->title('Reporte preparado.')
                        ->body('Haz clic aquí para ver los resultados.')
                        ->danger()
                        ->actions([
                            \Filament\Actions\Action::make('Descargar informe')
                                ->button()
                                ->url($ruta, true) // true = abrir en nueva pestaña
                        ])
                        ->send();

                })
                ->openUrlInNewTab(),

            Actions\Action::make('Crear')
                ->label('LEVANTAR INVENTARIO')
                ->color('success')
                ->extraAttributes(['class' => 'font-semibold font-3xl'])
                ->icon('heroicon-o-plus-circle')
                ->iconSize(IconSize::Large)
                ->url('/admin/inventories/create'),
        ];
    }
}
