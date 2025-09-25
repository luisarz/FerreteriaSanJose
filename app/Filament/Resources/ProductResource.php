<?php

namespace App\Filament\Resources;

use App\Filament\Forms\CreateClienteForm;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\HtmlString;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $label = 'Prodúctos';
    protected static ?string $navigationGroup = 'Almacén';
    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'sku', 'bar_code'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Prodúcto' => $record->name,
            'sku' => $record->sku,
            'Codigo de Barra' => $record->bar_code,
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del prodúcto')
                    ->compact()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->inlineLabel(false)
//                            ->columnSpanFull()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('aplications')
                            ->placeholder('Separar con punto y comas (;)')
//                            ->columnSpanFull()
                            ->inlineLabel(false)
                            ->label('Aplicaciones'),
                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('bar_code')
                            ->label('Código de barras')
                            ->maxLength(255)
                            ->default(null),

                        Forms\Components\Select::make('category_id')
                            ->label('Categoría')
                            ->relationship(
                                name: 'category',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereNotNull('parent_id')
                            )
                            ->preload()
                            //->searchable()
                            ->createOptionForm([
                                Forms\Components\BelongsToSelect::make('parent_id')
                                    ->relationship('category', 'name')
                                    ->nullable()
                                    ->placeholder('Seleccione una categoría')
                                    ->preload()
                                    //->searchable()
                                    ->label('Categoría padre'),

                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action
                                    ->label('Crear Categoria')
                                    ->color('success')
                                    ->icon('heroicon-o-plus');
                            })
                            ->createOptionUsing(function (array $data): int {
                                return \App\Models\Category::create($data)->id;
                            })
                            ->required(),

                        Forms\Components\Select::make('marca_id')
                            ->label('Marca')
                            ->relationship('marca', 'nombre')
                            //->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nombre')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('descripcion')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                return $action
                                    ->label('Crear marca')
                                    ->color('success')
                                    ->icon('heroicon-o-plus');
                            })
                            ->createOptionUsing(function (array $data): int {
                                return \App\Models\Marca::create($data)->id;
                            }),

        Forms\Components\Select::make('unit_measurement_id')
                            ->label('Unidad de medida')
                            ->preload()
                            //->searchable()
                            ->relationship('unitMeasurement', 'description')
                            ->required(),
//                        Forms\Components\MultiSelect::make('tribute_id')
//                            ->label('Impuestos')
//                            ->preload()
//                            //->searchable()
//                            ->relationship('tributes', 'name'),

                        Forms\Components\Section::make('Configuración')
                            ->schema([
                                Forms\Components\Toggle::make('is_service')
                                    ->label('Es un servicio')
                                    ->required(),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Activo')
                                    ->default(true)
                                    ->required(),
                                Forms\Components\Toggle::make('is_grouped')
                                    ->label('Compuesto')
                                    ->default(false)
                                    ->hidden()
                                    ->required(),
                                Forms\Components\Toggle::make('is_taxed')
                                    ->label('Gravado')
                                    ->default(true)
                                    ->required(),
                            ])->columns(3),

                        Forms\Components\FileUpload::make('images')
                            ->directory('products')
                            ->image()
                            ->openable()
                            ->columnSpanFull(),

                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

//                Tables\Columns\Layout\Grid::make()
//                    ->columns(1)
//                    ->schema([
//                        Tables\Columns\Layout\Split::make([
//                            Tables\Columns\Layout\Grid::make()
//                                ->columns(1)
//                                ->schema([
                                    Tables\Columns\ImageColumn::make('images')
                                        ->placeholder('Sin imagen')
                                        ->defaultImageUrl(url('storage/products/noimage.png'))
                                        ->openUrlInNewTab()
                                        ->square(),


//                                ])->grow(false),
//                            Tables\Columns\Layout\Stack::make([
                Tables\Columns\TextColumn::make('id')
                    ->label('Codigo')
                    ->sortable()
                    ->wrap(),
                    //->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Producto')
//                                    ->weight(FontWeight::SemiBold)
                    ->sortable()
//                                    ->icon('heroicon-s-cube')
                    ->wrap()
//                                    ->formatStateUsing(fn($state, $record) => $record->deleted_at ? "<span style='text-decoration: line-through; color: red;'>$state</span>" : $state)
                    ->html(),
                    //->searchable(),
                Tables\Columns\TextColumn::make('unitMeasurement.description')
                    ->label('Presentación')
//                    ->icon('heroicon-s-scale')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Linea')
//                    ->icon('heroicon-s-wrench-screwdriver')
                    ->sortable(),
                Tables\Columns\TextColumn::make('marca.nombre')
//                    ->icon('heroicon-s-check-badge')
                    ->sortable(),
                Tables\Columns\TextColumn::make('aplications')
                    ->label('Aplicaicones')
                    ->badge()
//                    ->icon('heroicon-s-cog')
                    ->sortable()
                    ->separator(';'),
                    //->searchable(),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->copyable()
//                                    ->icon('heroicon-s-qr-code')
                    ->copyMessage('SKU  copied'),
                    //->searchable(),
                Tables\Columns\BooleanColumn::make('is_grouped')
                    ->label('Servicio')
                    ->trueIcon('heroicon-o-server-stack')
                    ->falseIcon('heroicon-o-server')
                    ->sortable(),


                Tables\Columns\TextColumn::make('bar_code')
//                    ->icon('heroicon-s-code-bracket-square')
                    ->label('C. Barras')
                    ->toggleable(isToggledHiddenByDefault: true)
                    //->searchable(),




//                            ])->extraAttributes([
//                                'class' => 'space-y-2'
//                            ])
//                                ->grow(),


//                        ]),

//                    ]),


            ])
//            ->contentGrid([
//                'md' => 3,
//                'xs' => 4,
//            ])
            ->paginationPageOptions([
                5, 10, 25, 50, 100 // Define your specific pagination limits here
            ])
            ->filters([
                Filter::make('product_name')
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label('Producto')
                            ->placeholder('Buscar por nombre o código de barra'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['value'], function ($query, $value) {
                                $query->where('name', 'like', "%{$value}%")
                                    ->orWhere('bar_code', 'like', "%{$value}%");
                            });
                    }),

                //
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Categoría')
                    //->searchable()
                    ->preload()
                    ->relationship('category', 'name')
                    ->options(fn() => \App\Models\Category::pluck('name', 'id')->toArray())
                    ->default(null),
                Tables\Filters\SelectFilter::make('marca_id')
                    ->label('Marca')
                    //->searchable()
                    ->preload()
                    ->relationship('marca', 'nombre')
                    ->options(fn() => \App\Models\Marca::pluck('nombre', 'id')->toArray())
                    ->default(null),
                Tables\Filters\TrashedFilter::make(),


            ],layout: FiltersLayout::AboveContent)->filtersFormColumns(2)
            ->actions([

                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Ver')->iconSize(IconSize::Large),
                    Tables\Actions\EditAction::make()->label('Modificar')->iconSize(IconSize::Large)->color('warning'),
                    Tables\Actions\ReplicateAction::make()->label('Replicar')->iconSize(IconSize::Large),
                    Tables\Actions\DeleteAction::make()->label('Eliminar')->iconSize(IconSize::Large)->color('danger'),
                    Tables\Actions\RestoreAction::make()->label('Restaurar')->iconSize(IconSize::Large)->color('success'),
                ])->link()
                    ->label('Acciones'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
//                    ExportAction::make(),
                ])
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
            'index' => Pages\ListProducts::route('/'),
//            'create' => Pages\CreateProduct::route('/create'),
//            'view' => Pages\CreateProduct::route('/view'),
//            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

}
