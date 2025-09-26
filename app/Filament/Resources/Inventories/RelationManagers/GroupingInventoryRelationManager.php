<?php

namespace App\Filament\Resources\Inventories\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Auth;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Exception;
use App\Models\Inventory;
use App\Models\InventoryGrouped;
use App\Models\Price;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class GroupingInventoryRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoriesGrouped';
    protected static ?string $label = "Inventarios agrupados";
    protected static ?string $title = "Inventarios Agregados";

    protected static ?string $badgeColor = 'danger';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Inventario a agrupar')
                    ->schema([
                        Select::make('inventory_child_id')
                            ->label('Inventario')
                            ->searchable()
                            ->preload(true)
                            ->live()
                            ->debounce(300)
                            ->columnSpanFull()
                            ->inlineLabel(false)
                            ->getSearchResultsUsing(function (string $query, callable $get) {
                                $whereHouse = Auth::user()->employee->branch_id; // Sucursal del usuario
                                if (strlen($query) < 2) {
                                    return []; // No buscar si el texto es muy corto
                                }
                                $keywords = explode(' ', $query);

                                return Inventory::with([
                                    'product:id,name,sku,bar_code,aplications',
                                    'prices' => function ($q) {
                                        $q->where('is_default', 1)->select('id', 'inventory_id', 'price'); // Carga solo el precio predeterminado
                                    },
                                ])
                                    ->where('branch_id', $whereHouse) // Filtra por sucursal
                                    ->whereHas('prices', function ($q) {
                                        $q->where('is_default', 1); // Verifica que tenga un precio predeterminado
                                    })
                                    ->whereHas('product', function ($q) use ($keywords) {
                                        $q->where(function ($queryBuilder) use ($keywords) {
                                            foreach ($keywords as $word) {
                                                $queryBuilder->where('name', 'like', "%{$word}%")
                                                    ->orWhere('sku', 'like', "%{$word}%")
                                                    ->orWhere('bar_code', 'like', "%{$word}%");
                                            }
                                        });


                                    })
                                    ->select(['id', 'branch_id', 'product_id', 'stock']) // Selecciona solo las columnas necesarias
                                    ->limit(50) // Limita el número de resultados
                                    ->get()
                                    ->mapWithKeys(function ($inventory) {
                                        $price = optional($inventory->prices->first())->price ?? 0; // Obtén el precio predeterminado
                                        $displayText = "{$inventory->product->name} - Cod: {$inventory->product->bar_code} - STOCK: {$inventory->stock} - $ {$price}";
                                        return [$inventory->id => $displayText];
                                    });
                            })
                            ->getOptionLabelUsing(function ($value) {
                                $inventory = Inventory::with('product')->find($value);
                                return $inventory
                                    ? "{$inventory->product->name} - SKU: {$inventory->product->sku} - Codigo: {$inventory->product->bar_code}"
                                    : 'Producto no encontrado';
                            })
                            ->required(),

                        TextInput::make('quantity')
                            ->label('Cantidad a descontar')
                            ->inlineLabel(false)
                            ->required()
                            ->numeric(),

                        Toggle::make('is_active')
                            ->label('Predeterminado'),
                    ]),
            ]);
        // Asegúrate de que estás usando el modelo correcto

    }


    public function table(Table $table): Table
    {
        $inventory = $this->ownerRecord;

        return $table
            ->searchable()
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('inventoryChild.product.name')
                    ->searchable()
                    ->label('Inventario Agrupado'),
                TextColumn::make('quantity')
                    ->numeric()
                    ->label('Cantidad por item de venta'),


            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }


    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        try {
            $product_id=$ownerRecord->product_id;
            $product=Product::find($product_id);
            $is_grouped=$product->is_grouped;
            return $is_grouped === 1;
        }catch (Exception $exception){
            throw new Exception($exception->getMessage());
        }
    }
    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        return static::$badge;
    }
    public function getContentTabIcon(): ?string
    {
        return 'heroicon-m-cog';
    }
    public function hasCombinedRelationManagerTabsWithForm(): bool
    {
        return true;
    }


}
