<?php

namespace App\Filament\Resources\Inventories\Pages;

use App\Models\Category;
use App\Models\Marca;
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
                ->modalDescription('Seleccione los filtros para generar la hoja de conteo. Deje en blanco para incluir todos los productos.')
                ->modalSubmitActionLabel('Generar PDF')
                ->schema([
                    Select::make('category_id')
                        ->label('Categoría')
                        ->placeholder('Todas las categorías')
                        ->options(fn () => Category::whereNotNull('parent_id')->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload(),
                    Select::make('marca_id')
                        ->label('Marca')
                        ->placeholder('Todas las marcas')
                        ->options(fn () => Marca::where('estado', true)->orderBy('nombre')->pluck('nombre', 'id'))
                        ->searchable()
                        ->preload(),
                ])->action(function (array $data) {
                    $params = [];

                    if (!empty($data['category_id'])) {
                        $params['category_id'] = $data['category_id'];
                    }
                    if (!empty($data['marca_id'])) {
                        $params['marca_id'] = $data['marca_id'];
                    }

                    $url = route('inventory.counting.pdf', $params);

                    return redirect()->away($url);
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
