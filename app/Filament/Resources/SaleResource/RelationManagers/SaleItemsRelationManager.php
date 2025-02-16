<?php

namespace App\Filament\Resources\SaleResource\RelationManagers;

use App\Models\Inventory;
use App\Models\Price;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RetentionTaxe;
use App\Models\SaleItem;
use App\Models\Tribute;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Sale;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Svg\Tag\Image;
use Symfony\Component\Console\Input\Input;

class SaleItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'saleDetails';
    protected static ?string $title="Prodúctos agregados";
    protected static ?string $pollingInterval = '1s';


    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make('')
                    ->schema([

                        Forms\Components\Grid::make(12)
                            ->schema([

                                Section::make('Venta')
                                    ->icon('heroicon-o-user')
                                    ->iconColor('success')
                                    ->compact()
                                    ->schema([
                                        Forms\Components\TextInput::make('aplications')
                                            ->inlineLabel(false)
                                            ->columnSpanFull()
                                            ->label('Aplicaciones'),

                                        Select::make('inventory_id')
                                            ->label('Producto')
                                            ->searchable()
                                            ->preload(true)
                                            ->live()
                                            ->debounce(300)
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
                                            ->extraAttributes([
//                                                'class' => 'text-sm text-gray-700 font-semibold bg-gray-100 rounded-md', // Estilo de TailwindCSS
                                            ])
                                            ->required()
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $invetory_id = $get('inventory_id');

                                                $price = Price::with('inventory', 'inventory.product')->where('inventory_id', $invetory_id)->Where('is_default', true)->first();
                                                if ($price && $price->inventory) {
                                                    $set('price', $price->price);
                                                    $set('quantity', 1);
                                                    $set('discount', 0);
                                                    $set('minprice', $price->inventory->cost_with_taxes);

                                                    $this->calculateTotal($get, $set);
                                                } else {
                                                    $set('price', $price->price ?? 0);
                                                    $set('quantity', 1);
                                                    $set('discount', 0);
                                                    $this->calculateTotal($get, $set);
                                                }

//
                                                $images = is_array($price->inventory->product->images ?? null)
                                                    ? $price->inventory->product->images
                                                    : [$price->inventory->product->images ?? null];
                                                // Si no hay imágenes, asignar una imagen por defecto
                                                if (empty($images) || $images[0] === null) {
                                                    $images = ['products\/noimage.jpg']; // Ruta de la imagen por defecto
                                                }
                                                $set('product_image', $images);


                                            }),


                                        Forms\Components\TextInput::make('quantity')
                                            ->label('Cantidad')
                                            ->step(1)
                                            ->numeric()
                                            ->live()
                                            ->debounce(300)
                                            ->columnSpan(1)
                                            ->required()
                                            ->live()
                                            ->extraAttributes(['onkeyup' => 'this.dispatchEvent(new Event("input"))'])
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $this->calculateTotal($get, $set);
                                            }),

                                        Forms\Components\TextInput::make('price')
                                            ->label('Precio')
                                            ->step(0.01)
                                            ->numeric()
                                            ->columnSpan(1)
                                            ->required()
                                            ->live()
                                            ->debounce(300)
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $this->calculateTotal($get, $set);
                                            }),

                                        Forms\Components\TextInput::make('discount')
                                            ->label('Descuento')
                                            ->step(0.01)
                                            ->prefix('%')
                                            ->numeric()
                                            ->live()
                                            ->columnSpan(1)
                                            ->required()
                                            ->debounce(300)
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $this->calculateTotal($get, $set);
                                            }),

                                        Forms\Components\TextInput::make('total')
                                            ->label('Total')
                                            ->step(0.01)
                                            ->readOnly()
                                            ->columnSpan(1)
                                            ->required(),

                                        Forms\Components\Toggle::make('is_except')
                                            ->label('Exento de IVA')
                                            ->columnSpan(1)
                                            ->live()
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $this->calculateTotal($get, $set);
                                            }),
                                        Forms\Components\Toggle::make('is_tarjet')
                                            ->label('Con tarjeta')
                                            ->columnSpan(1)
                                            ->live()
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $price = $get('price'); // Obtener el precio actual
                                                if ($get('is_tarjet')) {
                                                    $set('price', $price * 1.05);
                                                } else {
                                                    $set('price', $price * 0.95);
                                                }
                                                $this->calculateTotal($get, $set);
                                            }),

                                        Forms\Components\TextInput::make('minprice')
                                            ->label('Tributos')
                                            ->hidden(true)
                                            ->columnSpan(3)
                                            ->afterStateUpdated(function (callable $get, callable $set) {

                                            }),


                                    ])->columnSpan(9)
                                    ->extraAttributes([
                                        'class' => 'bg-blue-100 border border-blue-500 rounded-md p-2',
                                    ])
                                    ->columns(2),



                                Section::make('')
                                    ->compact()
                                    ->schema([
                                        Section::make('')
                                            ->compact()
                                            ->schema([
                                                Forms\Components\Textarea::make('description')
                                                    ->label('Descripción')
                                                    ->inlineLabel(false)
                                            ]),
                                        Section::make('Imagen')
                                            ->compact()
                                            ->schema([
                                                Forms\Components\FileUpload::make('product_image')
                                                    ->label('')
                                                    ->previewable(true)
                                                    ->openable()
                                                    ->storeFiles(false)
                                                    ->deletable(false)
                                                    ->disabled() // Desactiva el campo

                                                    ->image(),
                                            ]),




                                    ])
                                    ->extraAttributes([
//                                        'class' => 'bg-blue-100 border border-blue-500 rounded-md p-2',
                                    ])
                                    ->columnSpan(3)->columns(1),
                            ]),
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
                    ->formatStateUsing(fn ($record) => $record->inventory->product->name . '</br> ' . $record->description)
                    ->html()
                    ->label('Producto'),

                Tables\Columns\BooleanColumn::make('inventory.product.is_service')
                    ->label('Producto/Servicio')
                    ->trueIcon('heroicon-o-bug-ant') // Icono cuando `is_service` es true
                    ->falseIcon('heroicon-o-cog-8-tooth') // Icono cuando `is_service` es false

                    ->tooltip(function ($record) {
                        return $record->inventory->product->is_service ? 'Es un servicio' : 'No es un servicio';
                    }),



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
                    ->modalHeading('Agregar Producto a venta')
                    ->label('Agregar Producto')
                    ->after(function (SaleItem $record, Component $livewire) {
                        $this->updateTotalSale($record);
                        $livewire->dispatch('refreshSale');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalWidth('7xl')
                    ->after(function (SaleItem $record, Component $livewire) {
                        $this->updateTotalSale($record);
                        $livewire->dispatch('refreshSale');

                    }),
                Tables\Actions\DeleteAction::make()
                    ->label('Quitar')
                    ->after(function (SaleItem $record, Component $livewire) {
                        $this->updateTotalSale($record);
                        $livewire->dispatch('refreshSale');

                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (SaleItem $record, Component $livewire) {
                            $selectedRecords = $livewire->getSelectedTableRecords();
                            foreach ($selectedRecords as $record) {
                                $this->updateTotalSale($record);
                            }
                            $livewire->dispatch('refreshSale');
                        }),

                ]),
            ]);
    }

    protected function calculateTotal(callable $get, callable $set)
    {
        try {
            $quantity = ($get('quantity') !== "" && $get('quantity') !== null) ? $get('quantity') : 0;
            $price = ($get('price') !== "" && $get('price') !== null) ? $get('price') : 0;
            $discount = ($get('discount') !== "" && $get('discount') !== null) ? $get('discount')/100 : 0;

            $is_except = $get('is_except');

            $total = $quantity * $price;

            if ($discount > 0) {
                $total -= $total * $discount;
            }
            if ($is_except) {
                $total -= ($total * 0.13);
            }

            // Formatear precio y total a dos decimales
            $price = round($price, 2);
            $total = round($total, 2);

            $set('price', $price);
            $set('total', $total);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }


    }

    protected function updateTotalSale(SaleItem $record)
    {
        $idSale = $record->sale_id;
        $sale = Sale::where('id', $idSale)->first();

//        dd($sale);
        if ($sale) {
            try {
                $ivaRate = Tribute::where('code', 20)->value('rate') ?? 0;
                $isrRate = RetentionTaxe::where('code', 22)->value('rate') ?? 0;

                $ivaRate = is_numeric($ivaRate) ? $ivaRate / 100 : 0;
                $isrRate = is_numeric($isrRate) ? $isrRate / 100 : 0;
                $montoTotal = SaleItem::where('sale_id', $sale->id)->sum('total') ?? 0;
//            dd($montoTotal);
                $neto = $ivaRate > 0 ? $montoTotal / (1 + $ivaRate) : $montoTotal;
                $iva = $montoTotal - $neto;
                $retention = $sale->have_retention ? $neto * 0.1 : 0;
                $sale->net_amount = round($neto, 2);
                $sale->taxe = round($iva, 2);
                $sale->retention = round($retention, 2);
                $sale->sale_total = round($montoTotal - $retention, 2);
                $sale->save();
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }


        }
    }

    public function isReadOnly(): bool
    {
        return false;
    }


}
