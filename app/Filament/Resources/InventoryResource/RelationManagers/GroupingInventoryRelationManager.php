<?php

namespace App\Filament\Resources\InventoryResource\RelationManagers;

use App\Models\Inventory;
use App\Models\InventoryGrouped;
use App\Models\Price;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class GroupingInventoryRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoriesGrouped';
    protected static ?string $label = "Inventarios agrupados";
    protected static ?string $title = "Inventarios Agregados";



    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Inventario a agrupar')
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
                                $whereHouse = \Auth::user()->employee->branch_id; // Sucursal del usuario
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

                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad a descontar')
                            ->inlineLabel(false)
                            ->required()
                            ->numeric(),

                        Forms\Components\Toggle::make('is_active')
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
                Tables\Columns\TextColumn::make('inventoryChild.product.name')
                    ->searchable()
                    ->label('Inventario Agrupado'),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->label('Cantidad por item de venta'),


            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }







}
