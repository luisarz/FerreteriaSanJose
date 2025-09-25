<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KardexResource\Pages;
use App\Models\Kardex;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Filament\Tables\Grouping\Group;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;


class KardexResource extends Resource
{
    protected static ?string $model = Kardex::class;

    protected static ?string $label = 'Kardex productos';
    protected static ?string $navigationGroup = 'Inventario';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('inventory.product.name')
//                    ->relationship('inventory.product', 'name')
                    ->required(),

                Forms\Components\DatePicker::make('date')
                    ->required(),
                Forms\Components\TextInput::make('operation_type')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('operation_id')
                    ->label('Tipo de Operación')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('operation_detail_id')
                    ->numeric()
                    ->default(null),
                Forms\Components\TextInput::make('document_type')
                    ->label('T. Documento')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('document_number')
                    ->label('Número')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('entity')
                    ->maxLength(255)
                    ->default(null),
//                Forms\Components\TextInput::make('nationality')
//                    ->maxLength(255)
//                    ->default(null),
                Forms\Components\TextInput::make('inventory_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('previous_stock')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('stock_in')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('stock_out')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('stock_actual')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('money_in')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('money_out')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('money_actual')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('sale_price')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('purchase_price')
                    ->required()
                    ->numeric()
                    ->default(0.00),
            ]);
    }

    public static function table(Table $table): Table
    {

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('inventory_id')
                    ->label('Inventario')
//                    ->searchable(isIndividual: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d-m-Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_number')
                    ->label('N°')
                    ->searchable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Tipo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('entity')
                    ->label('Razon Social')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nationality')
                    ->label('Nacionalidad')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

//                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('whereHouse.name')
                    ->label('Sucursal')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('inventory.product.name')
                    ->label('Producto')
//                    ->wrap(50)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('inventory.product.unitmeasurement.description')
                    ->label('U. Medida')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('operation_type')
                    ->label('Operación')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),


                Tables\Columns\TextColumn::make('previous_stock')
                    ->label('S. Anterior')
                    ->numeric()
                    ->extraAttributes(['class' => ' color-success bg-success-200']) // Agregar clases CSS para el borde
                    ->sortable(),
                ColumnGroup::make('DETALLE DE UNIDADES ( CANT)', [
                    Tables\Columns\TextColumn::make('stock_in')
                        ->label('Entrada')
                        ->numeric()
                        ->color('success') // 🟢 Aplica color de texto verde estilo Filament
                        ->formatStateUsing(fn ($state) => number_format($state, 2)) //
                        ->summarize(Sum::make()->label('Entrada'))
//                        ->extraAttributes(['class' => 'bg-success-200']) // Agregar clases CSS para el borde

                        ->sortable(),
                    Tables\Columns\TextColumn::make('stock_out')
                        ->label('Salida')
                        ->numeric()
                        ->sortable()
                        ->summarize(Sum::make()->label('Salida'))
                        ->color('danger') // 🔴 Aplica color de texto rojo estilo Filament
                        ->formatStateUsing(fn ($state) => number_format($state, 2)), // formato numérico

                    Tables\Columns\TextColumn::make('stock_actual')
                        ->label('Existencia')
                        ->numeric()
                        ->summarize(Sum::make()
                            ->label('Existencia')
                            ->numeric()
                            ->suffix(new HtmlString(' U'))
                        )
                        ->sortable(),
                ]),
                Tables\Columns\TextColumn::make('purchase_price')
                    ->money('USD', locale: 'USD')
                    ->label('Costo')
                    ->sortable(),
//                Tables\Columns\TextColumn::make('promedial_cost')
//                    ->money('USD', locale: 'USD')
//                    ->label('Costo Promedio')
//                    ->sortable(),
//                ColumnGroup::make('IMPORTE MONETARIO / PC', [
//
//                    Tables\Columns\TextColumn::make('money_in')
//                        ->label('ENTRADA')
//                        ->money('USD', locale: 'USD')
//                        ->sortable(),
//                    Tables\Columns\TextColumn::make('money_out')
//                        ->label('SALIDA')
//                        ->money('USD', locale: 'USD')
//                        ->sortable(),
//                    Tables\Columns\TextColumn::make('money_actual')
//                        ->label('EXISTENCIA')
//                        ->money('USD', locale: 'USD')
//                        ->sortable(),
//                ]),
//                Tables\Columns\TextColumn::make('sale_price')
//                    ->money('USD', locale: 'USD')
//                    ->label('Precio')
//                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->groups([
                Group::make('whereHouse.name')
                    ->label('Sucursal'),
                Group::make('inventory.product.name')
                    ->label('Inventario'),
                Group::make('date')
                    ->date()
                    ->label('Fecha Operación'),
            ])
            ->filters([
                DateRangeFilter::make('date')->timePicker24()
                    ->label('Fecha de operacion')
                    ->startDate(Carbon::now())
                    ->endDate(Carbon::now()),


                Filter::make('inventory_id')
                    ->label('Inventario ID')
                    ->form([
                        TextInput::make('inventory_id')
                            ->inlineLabel(false)
                            ->label('Inventario')
                            ->numeric(),
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['inventory_id'])) {
                            $query->where('inventory_id', $data['inventory_id']);
                        }
                    }),
                Filter::make('Buscar por sucursal')
                    ->form([
                        Select::make('branch_id')
                            ->label('Sucursal')
                            ->inlineLabel(false)
                            ->relationship('wherehouse', 'name')
                            ->preload()
                            ->default(fn () => Auth::user()->employee->branch_id)
//                            ->visible(fn () => Auth::user()->hasRole(['super_admin','manager'])),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['branch_id'] ?? null, fn ($q, $id) => $q->where('branch_id', $id));
                    }),


            ],layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\DeleteAction::make('delete')
                    ->label('')

                    ->icon('heroicon-o-trash'),
                Tables\Actions\ViewAction::make()->label(''),
//                Tables\Actions\EditAction::make('edit')->label(''),
            ], position: Tables\Enums\ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
                    ExportAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withFilename(fn($resource) => $resource::getModelLabel() . '-' . date('Y-m-d'))
                                ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
                                ->withColumns([
                                    Column::make('updated_at'),
                                ]),

                        ]),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKardexes::route('/'),
            'create' => Pages\CreateKardex::route('/create'),
//            'edit' => Pages\EditKardex::route('/{record}/edit'),
        ];
    }
}
