<?php

namespace App\Filament\Resources\Products;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use App\Models\Category;
use App\Models\Marca;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Forms\CreateClienteForm;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
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
// use pxlrbt\FilamentExcel\Actions\Tables\ExportAction; // No compatible con Filament 4
// use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Support\HtmlString;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $label = 'Prodúctos';
    protected static string | \UnitEnum | null $navigationGroup = 'Almacén';
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

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del prodúcto')
                    ->compact()
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->inlineLabel(false)
//                            ->columnSpanFull()
                            ->maxLength(255),
                        TextInput::make('aplications')
                            ->placeholder('Separar con punto y comas (;)')
//                            ->columnSpanFull()
                            ->inlineLabel(false)
                            ->label('Aplicaciones'),
                        TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255)
                            ->default(null),
                        TextInput::make('bar_code')
                            ->label('Código de barras')
                            ->maxLength(255)
                            ->default(null),

                        Select::make('category_id')
                            ->label('Categoría')
                            ->relationship(
                                name: 'category',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn ($query) => $query->whereNotNull('parent_id')
                            )
                            ->preload()
                            //->searchable()
                            ->createOptionForm([
                                Select::make('parent_id')
                                    ->relationship('category', 'name')
                                    ->nullable()
                                    ->placeholder('Seleccione una categoría')
                                    ->preload()
                                    //->searchable()
                                    ->label('Categoría padre'),

                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionAction(function (\Filament\Actions\Action $action) {
                                return $action
                                    ->label('Crear Categoria')
                                    ->color('success')
                                    ->icon('heroicon-o-plus');
                            })
                            ->createOptionUsing(function (array $data): int {
                                return Category::create($data)->id;
                            })
                            ->required(),

                        Select::make('marca_id')
                            ->label('Marca')
                            ->relationship('marca', 'nombre')
                            //->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('nombre')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('descripcion')
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->createOptionAction(function (\Filament\Actions\Action $action) {
                                return $action
                                    ->label('Crear marca')
                                    ->color('success')
                                    ->icon('heroicon-o-plus');
                            })
                            ->createOptionUsing(function (array $data): int {
                                return Marca::create($data)->id;
                            }),

        Select::make('unit_measurement_id')
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

                        Section::make('Configuración')
                            ->schema([
                                Toggle::make('is_service')
                                    ->label('Es un servicio')
                                    ->required(),
                                Toggle::make('is_active')
                                    ->label('Activo')
                                    ->default(true)
                                    ->required(),
                                Toggle::make('is_grouped')
                                    ->label('Compuesto')
                                    ->default(false)
                                    ->hidden()
                                    ->required(),
                                Toggle::make('is_taxed')
                                    ->label('Gravado')
                                    ->default(true)
                                    ->required(),
                            ])->columns(3),

                        FileUpload::make('images')
                            ->disk('public')
                            ->visibility('public')
                            ->directory('products')
                            ->image()
                            ->openable()
                            ->imageEditor()
                            ->imageResizeMode('cover')
                            ->imageResizeTargetWidth(800)
                            ->imageResizeTargetHeight(800)
                            ->maxSize(1024) // 1MB máximo
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
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
                                    ImageColumn::make('images')
                                        ->disk('public')
                                        ->placeholder('Sin imagen')
                                        ->defaultImageUrl(url('storage/products/noimage.png'))
                                        ->openUrlInNewTab()
                                        ->square(),


//                                ])->grow(false),
//                            Tables\Columns\Layout\Stack::make([
                TextColumn::make('id')
                    ->label('Codigo')
                    ->sortable()
                    ->wrap(),
                    //->searchable(),
                TextColumn::make('name')
                    ->label('Producto')
//                                    ->weight(FontWeight::SemiBold)
                    ->sortable()
//                                    ->icon('heroicon-s-cube')
                    ->wrap()
//                                    ->formatStateUsing(fn($state, $record) => $record->deleted_at ? "<span style='text-decoration: line-through; color: red;'>$state</span>" : $state)
                    ->html(),
                    //->searchable(),
                TextColumn::make('unitMeasurement.description')
                    ->label('Presentación')
//                    ->icon('heroicon-s-scale')
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Linea')
//                    ->icon('heroicon-s-wrench-screwdriver')
                    ->sortable(),
                TextColumn::make('marca.nombre')
//                    ->icon('heroicon-s-check-badge')
                    ->sortable(),
                TextColumn::make('aplications')
                    ->label('Aplicaicones')
                    ->badge()
//                    ->icon('heroicon-s-cog')
                    ->sortable()
                    ->separator(';'),
                    //->searchable(),
                TextColumn::make('sku')
                    ->label('SKU')
                    ->copyable()
//                                    ->icon('heroicon-s-qr-code')
                    ->copyMessage('SKU  copied'),
                    //->searchable(),
                BooleanColumn::make('is_grouped')
                    ->label('Servicio')
                    ->trueIcon('heroicon-o-server-stack')
                    ->falseIcon('heroicon-o-server')
                    ->sortable(),


                TextColumn::make('bar_code')
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
                    ->schema([
                        TextInput::make('value')
                            ->label('Producto')
                            ->placeholder('Buscar por nombre o código de barra')
                            ->live(debounce: 500),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['value'], function ($query, $value) {
                                $query->where('name', 'like', "{$value}%")
                                    ->orWhere('bar_code', 'like', "{$value}%");
                            });
                    }),

                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->preload()
                    ->relationship('category', 'name')
                    ->options(fn() => Category::pluck('name', 'id')->toArray())
                    ->default(null),
                SelectFilter::make('marca_id')
                    ->label('Marca')
                    ->preload()
                    ->relationship('marca', 'nombre')
                    ->options(fn() => Marca::pluck('nombre', 'id')->toArray())
                    ->default(null),
                TrashedFilter::make(),

            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->filtersApplyAction(fn () => null)
            ->persistFiltersInSession()
            ->recordActions([

                ActionGroup::make([
                    ViewAction::make()->label('Ver')->iconSize(IconSize::Large),
                    EditAction::make()->label('Modificar')->iconSize(IconSize::Large)->color('warning'),
                    ReplicateAction::make()->label('Replicar')->iconSize(IconSize::Large),
                    DeleteAction::make()->label('Eliminar')->iconSize(IconSize::Large)->color('danger'),
                    RestoreAction::make()->label('Restaurar')->iconSize(IconSize::Large)->color('success'),
                ])->link()
                    ->label('Acciones'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
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
            'index' => ListProducts::route('/'),
//            'create' => Pages\CreateProduct::route('/create'),
//            'view' => Pages\CreateProduct::route('/view'),
//            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

}
