<?php

namespace App\Filament\Resources;

use App\Filament\Exports\InventoryExporter;
use App\Filament\Resources\InventoryResource\Pages;
use App\Filament\Resources\InventoryResource\RelationManagers;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Tribute;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ReplicateAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Actions\Action;
use Filament\Tables\Actions;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;

class InventoryResource extends Resource
{
    protected static function getWhereHouse(): string
    {
        return \Auth::user()->employee->wherehouse->name ?? 'N/A'; // Si no hay valor, usa 'N/A'
    }

    protected static ?string $model = Inventory::class;
    protected static ?string $navigationGroup = 'Inventario';
    protected static ?string $label = 'Inventario'; // Singular
    protected static ?string $pluralLabel = "Lista de inventario";
    protected static ?string $badgeColor = 'danger';



//

    public static function form(Form $form): Form
    {
        $tax = Tribute::find(1)->select('rate', 'is_percentage')->first();
        if (!$tax) {
            $tax = (object)['rate' => 0, 'is_percentage' => false];
        }
        $divider = ($tax->is_percentage) ? 100 : 1;
        $iva = $tax->rate / $divider;
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->compact()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Section::make('Informacion del Inventario')
                            ->columns(3)
                            ->compact()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->required()
                                    ->inlineLabel(false)
                                    ->preload()
                                    ->columnSpanFull()
                                    ->relationship('product', 'name')
                                    ->searchable(['name', 'sku'])
                                    ->placeholder('Seleccionar producto')
                                    ->loadingMessage('Cargando productos...')
                                    ->getOptionLabelsUsing(function ($record) {
                                        return "{$record->name} (SKU: {$record->sku})";  // Formato de la etiqueta
                                    }),

                                Forms\Components\Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->placeholder('Seleccionar sucursal')
                                    ->relationship('branch', 'name')
                                    ->preload()
                                    ->searchable(['name'])
                                    ->required(),

                                Forms\Components\TextInput::make('stock')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\Hidden::make('stock_actual')
                                    ->default(0) // Valor predeterminado para nuevos registros
                                    ->afterStateHydrated(function (Forms\Components\Hidden $component, $state, $record) {
                                        if ($record) {
                                            $component->state($record->stock);
                                        }
                                    }),

                                Forms\Components\TextInput::make('stock_min')
                                    ->label('Stock Minimo')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('stock_max')
                                    ->label('Stock Maximo')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                                Forms\Components\TextInput::make('cost_without_taxes')
                                    ->required()
                                    ->prefix('$')
                                    ->label('C. sin IVA')
                                    ->numeric()
                                    ->inputMode('decimal')
                                    ->hintColor('red')
                                    ->debounce(500) // Espera 500 ms después de que el usuario deje de escribir
                                    ->afterStateUpdated(function ($state, callable $set) use ($iva) {
                                        $costWithoutTaxes = $state ?: 0; // Valor predeterminado en 0 si está vacío
                                        $costWithTaxes = number_format($costWithoutTaxes * $iva, 2,'.',''); // Cálculo del costo con impuestos
                                        $costWithTaxes += $costWithoutTaxes; // Suma el costo sin impuestos
                                        $set('cost_with_taxes',number_format( $costWithTaxes,2,'.','')); // Actualiza el campo
                                    })
                                    ->default(0.00),
                                Forms\Components\TextInput::make('cost_with_taxes')
                                    ->label('C. + IVA')
                                    ->required()
                                    ->readOnly()
                                    ->numeric()
                                    ->prefix('$')
                                    ->default(0.00),


                            ]),
                        Forms\Components\Section::make('Configuración')
                            ->columns(3)
                            ->compact()
                            ->schema([
                                Forms\Components\Toggle::make('is_stock_alert')
                                    ->label('Alerta de stock minimo')
                                    ->default(true)
                                    ->required(),
                                Forms\Components\Toggle::make('is_expiration_date')
                                    ->label('Tiene vencimiento')
                                    ->default(true)
                                    ->required(),
                                Forms\Components\Toggle::make('is_active')
                                    ->default(true)
                                    ->label('Activo')
                                    ->required(),
                            ]) // Fin de la sección de configuración

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\Layout\Grid::make()
                    ->columns(1)
                    ->schema([
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\Layout\Grid::make()
                                ->columns(1)
                                ->schema([
                                    Tables\Columns\ImageColumn::make('product.images')
                                        ->placeholder('Sin imagen')
                                        ->defaultImageUrl(url('storage/products/noimage.png'))
                                        ->openUrlInNewTab()
                                        ->height(150)
                                        ->width(120)
                                        ->extraAttributes([
                                            'class' => 'rounded-md',
                                            'loading' => 'lazy'
                                        ])
                                ])->grow(false),
                            Tables\Columns\Layout\Stack::make([
                                Tables\Columns\TextColumn::make('product.name')
                                    ->label('Producto')
                                    ->weight(FontWeight::Medium)
                                    ->sortable()
                                    ->wrap()

                                    ->icon('heroicon-s-cube')
                                    ->searchable()
                                    ->sortable(),
                                Tables\Columns\TextColumn::make('id')
                                    ->label('Producto')
                                    ->color('danger')
                                    ->weight(FontWeight::Medium)
                                    ->sortable()
                                    ->icon('heroicon-o-qr-code')
                                    ->searchable()
                                    ->sortable(),
                                Tables\Columns\TextColumn::make('product.aplications')
                                    ->label('Aplicaciones')
                                    ->badge()
                                    ->icon('heroicon-s-cog')
                                    ->searchable()
                                    ->separator(';'),
                                Tables\Columns\TextColumn::make('product.sku')
                                    ->label('SKU')
                                    ->copyable()
                                    ->copyable()
                                    ->copyMessage('SKU code copado')
                                    ->copyMessageDuration(1500)
                                    ->copyableState(fn(Inventory $record): string => "Color: {$record->color}")
                                    ->icon('heroicon-s-qr-code')
                                    ->searchable()
                                    ->sortable(),
                                Tables\Columns\TextColumn::make('branch.name')
                                    ->label('Sucursal')
                                    ->icon('heroicon-s-building-office-2')
                                    ->sortable(),
                                Tables\Columns\TextColumn::make('stock')
                                    ->numeric()
                                    ->icon('heroicon-s-circle-stack')
                                    ->getStateUsing(function ($record) {
//                                        return $record->stock>0?number_format($record->stock,2):'Sin Existencia';
                                        return $record->stock
                                            ? number_format($record['stock'], 2, '.')
                                            : 'Sin Stock';
                                    })
                                    ->color(function ($record) {
                                        // Si no hay stock, el texto será rojo
                                        return $record->stock > 0 ? null : 'danger';
                                    })
                                    ->weight(FontWeight::Medium)
                                    ->sortable(),
                                Tables\Columns\TextColumn::make('prices')
                                    ->numeric()
                                    ->icon('heroicon-s-currency-dollar')
                                    ->weight(FontWeight::Bold)
                                    ->getStateUsing(function ($record) {
                                        // Filtrar el precio donde 'is_default' sea igual a 1
                                        $defaultPrice = collect($record->prices)->firstWhere('is_default', 1);

                                        // Retornar el precio formateado como moneda con signo de dólar o 'Sin precio' si no se encuentra
                                        return $defaultPrice
                                            ? '$' . number_format($defaultPrice['price'], 2)
                                            : 'Sin precio';
                                    }),
//                                    ->sortable(),
                            ])->extraAttributes([
                                'class' => 'space-y-2'
                            ])
                                ->grow(),

                        ])


                    ]),

            ])
            ->contentGrid([
                'md' => 3,
                'xs' => 4,
            ])
//            ->deferLoading()
            ->striped()
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('branch_id')
                    ->relationship('branch', 'name')
                    ->label('Sucursal')
                    ->preload()
                    ->default(\Auth::user()->employee->wherehouse->id)
                    ->placeholder('Buscar por sucursal'),
//
            ])->filtersFormColumns(2)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ReplicateAction::make()
                        ->form([
                            Forms\Components\Select::make('branch_did')
                                ->relationship('branch', 'name')
                                ->label('Sucursal Destino')
                                ->required()
                                ->placeholder('Ingresa el ID de la sucursal'),
                        ])
                        ->beforeReplicaSaved(function (Inventory $record, Actions\Action $action, $replica, array $data): void {
                            try {
                                $existencia = Inventory::withTrashed()
                                    ->where('product_id', $record->product_id)
                                    ->where('branch_id', $data['branch_did'])
                                    ->first();
                                if ($existencia) {
                                    // Si el registro está eliminado
                                    if ($existencia->trashed()) {
                                        Notification::make('Inventario Eliminado')
                                            ->title('Replicar Inventario')
                                            ->danger()
                                            ->body('El inventario ya existe en la sucursal destino, pero el estado es eliminado, restarualo para poder replicarlo')
                                            ->send();
                                        $action->halt(); // Detener la acción si el inventario está eliminado
                                    } else {
                                        // Si el registro existe y no está eliminado
                                        Notification::make('Registro Duplicado')
                                            ->danger()
                                            ->body('Ya existe un registro con el producto ' . $record->product->name . ' en la sucursal ' . $record->branch->name . '.')
                                            ->send();
                                        $action->halt(); // Detener la acción si se encuentra un registro duplicado
                                    }
                                }
                            } catch (\Exception $e) {
                                $action->halt(); // Detener la acción en caso de error
                            }
                        }),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\ForceDeleteAction::make(),
                ])
                    ->link()
                    ->label('Acciones'),
            ])
            ->persistFiltersInSession()
            ->recordUrl(null)
            ->headerActions([

            ])
            ->searchable('product.name', 'product.sku', 'branch.name', 'product.aplications')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportAction::make()
                        ->exporter(InventoryExporter::class)
                        ->formats([
                            ExportFormat::Csv,
                        ])
                        ->formats([
                            ExportFormat::Xlsx,
                        ])
                        // or
                        ->formats([
                            ExportFormat::Xlsx,
                            ExportFormat::Csv,
                        ])

                ]),
            ]);
    }

    public static function getRelations(): array
    {
        $relations = [];



        return [
            RelationManagers\PricesRelationManager::class,
            RelationManagers\GroupingInventoryRelationManager::class,
        ];
    }




    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventories::route('/'),
            'create' => Pages\CreateInventory::route('/create'),
            'edit' => Pages\EditInventory::route('/{record}/edit'),
        ];
    }


}
