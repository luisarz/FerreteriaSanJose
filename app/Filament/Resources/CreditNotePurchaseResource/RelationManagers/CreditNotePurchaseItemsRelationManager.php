<?php

namespace App\Filament\Resources\CreditNotePurchaseResource\RelationManagers;

use App\Models\Inventory;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Component;

class CreditNotePurchaseItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseItems';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del producto a Comprar')
//                    ->description('Agregue los productos que desea vender')
                    ->icon('heroicon-o-shopping-cart')
                    ->columns(3)
                    ->schema([
                        Select::make('inventory_id')
                            ->label('Producto')
                            ->searchable()
                            ->debounce(500)
                            ->columnSpanFull()
                            ->inlineLabel(false)
                            ->getSearchResultsUsing(function (string $query, callable $get) {
                                $whereHouse = \Auth::user()->employee->branch_id; // Sucursal del usuario
                                $aplications = $get('aplications');
                                if (strlen($query) < 2) {
                                    return []; // No buscar si el texto es muy corto
                                }
                                // Dividir el texto ingresado en palabras clave
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
                                    ->whereHas('product', function ($q) use ($aplications, $keywords) {
                                        $q->where(function ($queryBuilder) use ($keywords) {
                                            foreach ($keywords as $word) {
                                                $queryBuilder->where('name', 'like', "%{$word}%")
                                                    ->orWhere('sku', 'like', "%{$word}%")
                                                    ->orWhere('bar_code', 'like', "%{$word}%");
                                            }
                                        });

                                        if (!empty($aplications)) {
                                            $q->where('aplications', 'like', "%{$aplications}%");
                                        }
                                    })
                                    ->select(['id', 'branch_id', 'product_id', 'stock']) // Selecciona solo las columnas necesarias
                                    ->limit(50) // Limita el número de resultados
                                    ->get()
                                    ->mapWithKeys(function ($inventory) {
                                        $price = optional($inventory->prices->first())->price; // Obtén el precio predeterminado
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
                            ->label('Cantidad')
                            ->step(1)
                            ->numeric()
                            ->debounce(500)
                            ->columnSpan(1)
                            ->required()
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $this->calculateTotal($get, $set);
                            }),

                        Forms\Components\TextInput::make('price')
                            ->label('Precio')
                            ->step(0.01)
                            ->numeric()
                            ->columnSpan(1)
                            ->required()
                            ->debounce(500)
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $this->calculateTotal($get, $set);
                            }),

                        Forms\Components\TextInput::make('discount')
                            ->label('Descuento')
                            ->step(0.01)
                            ->prefix('%')
                            ->numeric()
                            ->columnSpan(1)
                            ->required()
                            ->default(0)
                            ->debounce(500)
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $this->calculateTotal($get, $set);
                            }),

                        Forms\Components\TextInput::make('total')
                            ->label('Total')
                            ->step(0.01)
                            ->columnSpan(1)
                            ->debounce(500)
                            ->afterStateUpdated(function (callable $get, callable $set) {
                                $total = ($get('total') !== "" && $get('total') !== null) ? $get('total') : 0;
                                $set('total', number_format($total, 2));
                                $quantity = ($get('quantity') !== "" && $get('quantity') !== null) ? $get('quantity') : 0;
                                $newPrice=($total/$quantity);
                                $set('price', number_format($newPrice, 2, '.', ''));

                                $this->calculateTotal($get, $set);
                            })
                            ->required(),

                    ]),


            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Sales Item')
            ->columns([
                Tables\Columns\TextColumn::make('inventory.product.name')
                    ->wrap()
                    ->label('Producto'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->columnSpan(1),
                Tables\Columns\TextColumn::make('price')
                    ->label('Precio')
                    ->money('USD', locale: 'en_US')
                    ->columnSpan(1),
                Tables\Columns\TextColumn::make('discount')
                    ->label('Descuento')
                    ->prefix('%')
                    ->numeric()
                    ->columnSpan(1),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('USD', locale: 'en_US')
                    ->columnSpan(1),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalWidth('7xl')
                    ->modalHeading('Agregar Producto a Compra')
                    ->label('Agregar Producto')
                    ->after(function (PurchaseItem $record,Component $livewire) {
                        $this->updateTotalPurchase($record);
                        $livewire->dispatch('refreshPurchase');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('7xl')
                    ->after(function (PurchaseItem $record,Component $livewire) {
                        $this->updateTotalPurchase($record);
                        $livewire->dispatch('refreshPurchase');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Quitar')
                    ->after(function (PurchaseItem $record,Component $livewire) {
                        $this->updateTotalPurchase($record);
                        $livewire->dispatch('refreshPurchase');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (PurchaseItem $record,Component $livewire) {
                            $selectedRecords = $livewire->getSelectedTableRecords();
                            foreach ($selectedRecords as $record) {
                                $this->updateTotalPurchase($record);
                            }
                        $livewire->dispatch('refreshPurchase');
                    }),
                ]),
            ]);
    }

    protected function afterDelete(): void
    {
        $this->updateParentTotal();
    }


    protected function calculateTotal(callable $get, callable $set)
    {
        $quantity = ($get('quantity') !== "" && $get('quantity') !== null) ? $get('quantity') : 0;
        $price = ($get('price') !== "" && $get('price') !== null) ? $get('price') : 0;
        $discount = ($get('discount') !== "" && $get('discount') !== null) ? $get('discount') / 100 : 0;
        $is_except = $get('is_except') !== "" && $get('is_except') !== null && $get('is_except');


        $total = $quantity * $price;
        if ($discount > 0) {
            $total -= $total * $discount;
        }
        if ($is_except) {
            $total -= ($total * 0.13);
        }

        $set('total', $total);
    }

    protected function updateTotalPurchase(PurchaseItem $record)
    {
        $purchase = Purchase::find($record->purchase_id);
        if ($purchase) {
            $neto = PurchaseItem::where('purchase_id', $purchase->id)->sum('total');
            if (!is_numeric($neto)) {
                $neto = 0;
            }
            $iva = number_format($neto * 0.13, 2);
            if (!is_numeric($iva)) {
                $iva = 0;
            }
            $percepcion = 0;
            if ($purchase->have_perception) {
                $percepcion = $neto * 0.1;
            }
            if (!is_numeric($percepcion)) {
                $percepcion = 0;
            }
            $totalPurchase = $neto + $iva + $percepcion;
            $total = preg_replace('/[^\d.]/', '', $totalPurchase);
            if (!is_numeric($total)) {
                $total = 0;  // Set to 0 if not valid
            }
            $purchase->net_value = $neto;
            $purchase->taxe_value = $iva;
            $purchase->perception_value = $percepcion;
            $purchase->purchase_total = $total;
            $purchase->save();
        }

    }
}
