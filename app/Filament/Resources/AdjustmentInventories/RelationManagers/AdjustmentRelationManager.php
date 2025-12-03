<?php

namespace App\Filament\Resources\AdjustmentInventories\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Auth;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Exception;
use App\Models\Distrito;
use App\Models\Inventory;
use App\Models\precio_unitario;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\RetentionTaxe;
use App\Models\adjustmentInventoryItems;
use App\Models\Tribute;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use App\Models\adjustmentInventory;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Svg\Tag\Image;
use Symfony\Component\Console\Input\Input;

class AdjustmentRelationManager extends RelationManager
{
    protected static string $relationship = 'adjustItems';
    protected static ?string $title = "Prodúctos agregados";
    protected static ?string $label="Producto a procesar";
    protected static ?string $pollingInterval = null;


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('')
                    ->schema([

                        Grid::make(12)
                            ->schema([

                                Section::make('Ajuste')
                                    ->icon('heroicon-o-user')
                                    ->iconColor('success')
                                    ->compact()
                                    ->schema([
                                        Select::make('inventory_id')
                                            ->label('Producto')
                                            ->searchable()
                                            ->preload(true)
                                            ->debounce(300)
                                            ->columnSpanFull()
                                            ->inlineLabel(false)
                                            ->getSearchResultsUsing(function (string $query, callable $get) {
                                                $whereHouse = Auth::user()->employee->branch_id; // Sucursal del usuario
                                                if (strlen($query) < 2) {
                                                    return []; // No buscar si el texto es muy corto
                                                }
                                                $keywords = $query;
                                                return Inventory::with([
                                                    'product:id,name,sku,bar_code,aplications',

                                                ])
                                                    ->where('branch_id', $whereHouse) // Filtra por sucursal
                                                    ->whereHas('prices', function ($q) {
                                                        $q->where('is_default', 1); // Verifica que tenga un precio predeterminado
                                                    })
                                                    ->whereHas('product', function ($q) use ($keywords) {
                                                        $q->where(function ($queryBuilder) use ($keywords) {
//                                                            foreach ($keywords as $word) {
                                                            $queryBuilder->where('name', 'like', "%$keywords%")
                                                                ->orWhere('sku', 'like', "%$keywords%")
                                                                ->orWhere('bar_code', 'like', "%$keywords%");
//                                                            }
                                                        });


                                                    })
                                                    ->select(['id', 'branch_id', 'product_id', 'stock','cost_with_taxes']) // Selecciona solo las columnas necesarias
                                                    ->limit(50) // Limita el número de resultados
                                                    ->get()
                                                    ->mapWithKeys(function ($inventory) {
                                                        $displayText = "{$inventory->product->name} - Cod: {$inventory->product->sku} - STOCK: {$inventory->stock} - $ {$inventory->cost_with_taxes}";
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

                                                $price = inventory::where('id', $invetory_id)->first();
                                                if ($price && $price->inventory) {
                                                    $set('precio_unitario', $price->cost_with_taxes);
                                                    $set('cantidad', 1);
                                                    $set('minprice', $price->inventory->cost_with_taxes);

                                                    $this->calculateTotal($get, $set);
                                                } else {
                                                    $set('precio_unitario', $price->cost_with_taxes ?? 0);
                                                    $set('cantidad', 1);
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
//                                        Select::make('priceList')
//                                            ->label('Precios')
//                                            ->inlineLabel(false)
//                                            ->options(function (callable $get) {
//                                                $inventory_id = $get('inventory_id');
//
//                                                if (!$inventory_id) {
//                                                    return [];
//                                                }
//
//                                                // Fetch precio_unitario details and format them
//                                                $options = precio_unitario::where('inventory_id', $inventory_id)
//                                                    ->get()
//                                                    ->mapWithKeys(function ($price) {
//                                                        return [$price->id => "{$price->name} - $: {$price->precio_unitario}"];
//                                                    });
//
//                                                return $options;
//                                            })
//                                            ->reactive() // Ensure the field is reactive when the value changes
//                                            ->afterStateUpdated(function (callable $get, $state, callable $set) {
//                                                // This will automatically set the precio_unitario to the corresponding precio_unitario field when the select value changes
//                                                $price = precio_unitario::find($state);
//                                                if ($price) {
//                                                    $set('precio_unitario', $price->precio_unitario ?? 0); // Set the 'precio_unitario' field with the selected precio_unitario
//                                                    // Call the calculateTotal method after updating the precio_unitario
//                                                    $this->calculateTotal($get, $set);
//                                                }
//                                            }),

                                        TextInput::make('cantidad')
                                            ->label('Cantidad')
                                            ->step(1)
                                            ->numeric()
                                            ->live(onBlur: true)
                                            ->columnSpan(1)
                                            ->required()
                                            ->live()
                                            ->extraAttributes(['onkeyup' => 'this.dispatchEvent(new Event("input"))'])
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $this->calculateTotal($get, $set);
                                            }),

                                        TextInput::make('precio_unitario')
                                            ->label('Precio')
                                            ->step(0.01)
                                            ->numeric()
                                            ->columnSpan(1)
                                            ->required()
//                                            ->readOnly()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $this->calculateTotal($get, $set);
                                            }),



                                        TextInput::make('total')
                                            ->label('Total')
                                            ->step(0.01)
                                            ->readOnly()
                                            ->readOnly()
                                            ->columnSpan(1)
                                            ->required(),


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
                                                Textarea::make('description')
                                                    ->label('Descripción')
                                                    ->inlineLabel(false)
                                            ]),
                                        Section::make('')
                                            ->compact()
                                            ->schema([
                                                FileUpload::make('product_image')
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
                TextColumn::make('inventory.product.name')
                    ->wrap()
                    ->formatStateUsing(fn($record) => $record->inventory->product->name . '</br> ' . $record->description)
                    ->html()
                    ->label('Producto'),

                BooleanColumn::make('inventory.product.is_service')
                    ->label('Producto/Servicio')
                    ->trueIcon('heroicon-o-bug-ant') // Icono cuando `is_service` es true
                    ->falseIcon('heroicon-o-cog-8-tooth') // Icono cuando `is_service` es false

                    ->tooltip(function ($record) {
                        return $record->inventory->product->is_service ? 'Es un servicio' : 'No es un servicio';
                    }),


                TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->numeric()
                    ->columnSpan(1),
                TextColumn::make('precio_unitario')
                    ->label('Precio')
                    ->money('USD', locale: 'en_US')
                    ->columnSpan(1),
             
                TextColumn::make('total')
                    ->label('Total')
                    ->summarize(Sum::make()->label('Total')->money('USD', locale: 'en_US'))
                    ->money('USD', locale: 'en_US')
                    ->columnSpan(1),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalWidth('7xl')
                    ->modalHeading('Agregar Producto a proceso')
                    ->label('Agregar Producto')
                    ->after(function (adjustmentInventoryItems $record, Component $livewire) {
                        $this->updateTotalSale($record);
                        $livewire->dispatch('refreshSale');
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('7xl')
                    ->after(function (adjustmentInventoryItems $record, Component $livewire) {
                        $this->updateTotalSale($record);
                        $livewire->dispatch('refreshSale');

                    }),
                DeleteAction::make()
                    ->label('Quitar')
                    ->after(function (adjustmentInventoryItems $record, Component $livewire) {
                        $this->updateTotalSale($record);
                        $livewire->dispatch('refreshSale');

                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function (adjustmentInventoryItems $record, Component $livewire) {
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
            $quantity = ($get('cantidad') !== "" && $get('cantidad') !== null) ? $get('cantidad') : 0;
            $price = ($get('precio_unitario') !== "" && $get('precio_unitario') !== null) ? $get('precio_unitario') : 0;
            $total = $quantity * $price;
            // Formatear precio y total a dos decimales
            $price = round($price, 2);
            $total = round($total, 2);
            $set('precio_unitario', $price);
            $set('total', $total);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }


    }

    protected function updateTotalSale(adjustmentInventoryItems $record)
    {
        $idSale = $record->adjustment_id;
        $sale = adjustmentInventory::where('id', $idSale)->first();

//        dd($sale);
        if ($sale) {
            try {
                $montoTotal = adjustmentInventoryItems::where('adjustment_id', $sale->id)->sum('total') ?? 0;
                $sale->monto = round($montoTotal , 2);
                $sale->save();
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }



}