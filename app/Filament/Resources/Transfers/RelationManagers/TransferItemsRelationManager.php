<?php

namespace App\Filament\Resources\Transfers\RelationManagers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Auth;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Exception;
use App\Models\Inventory;
use App\Models\Price;
use App\Models\RetentionTaxe;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\TransferItems;
use App\Models\Tribute;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Filament\Forms;
use Filament\Infolists\Components\ImageEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Transfer;
use Svg\Tag\Image;

class TransferItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'transferDetails';
    protected static ?string $label = 'Prodúctos agregados';
    protected static ?string $pollingInterval = '1s';


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('')
                    ->schema([

                        Grid::make(12)
                            ->schema([

                                Section::make('Traslado')
                                    ->icon('heroicon-o-user')
                                    ->iconColor('success')
                                    ->compact()
                                    ->schema([

                                        Select::make('inventory_id')
                                            ->label('Producto')
                                            ->searchable()
                                            ->live()
                                            ->debounce(300)
                                            ->columnSpanFull()
                                            ->inlineLabel(false)
                                            ->getSearchResultsUsing(function (string $query) {
                                                $whereHouse = Auth::user()->employee->branch_id;
                                                if (strlen($query) < 3) {
                                                    return []; // No cargar resultados hasta que haya al menos 3 letras
                                                }
                                                return Inventory::with('product')
                                                    ->where('branch_id', $whereHouse)
                                                    ->whereHas('product', function ($q) use ($query) {
                                                        $q->where('name', 'like', "%{$query}%")
                                                            ->orWhere('sku', 'like', "%{$query}%")
                                                            ->orWhere('bar_code', 'like', "%{$query}%");
                                                    })
                                                    ->limit(50) // Limita el número de resultados para evitar cargas pesadas
                                                    ->get()
                                                    ->mapWithKeys(function ($inventory) {
                                                        $displayText = "{$inventory->product->name} - SKU: {$inventory->product->sku} - Codigo: {$inventory->product->bar_code}";
                                                        return [$inventory->id => $displayText];
                                                    });
                                            })
                                            ->getOptionLabelUsing(function ($value) {
                                                $inventory = Inventory::with('product')->find($value);
                                                return $inventory
                                                    ? "{$inventory->product->name} - SKU: {$inventory->product->sku} - Codigo: {$inventory->product->bar_code}"
                                                    : 'Producto no encontrado';
                                            })
                                            ->required()
                                            ->afterStateUpdated(function (callable $get, callable $set, Action $action) {
                                                $inventory_id = $get('inventory_id');
                                                $whereHouseTo = $this->ownerRecord->wherehouse_to;
                                                $inventory = Inventory::with('product')->where('id', $inventory_id)->first();
                                                $existInDestiny = Inventory::where('product_id', $inventory->product->id)->where('branch_id', $whereHouseTo)->first();
                                                if (!$existInDestiny) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('El producto no existe en la sucursal destino')
                                                        ->danger()
                                                        ->send();
                                                    $set('inventory_id', null);
                                                        return null;
                                                }
                                                if ($inventory && $inventory->cost_without_taxes) {
                                                    $set('price', $inventory->cost_without_taxes);
                                                    $set('quantity', 1);
                                                    $this->calculateTotal($get, $set);
                                                } else {
                                                    $set('price', $inventory->cost_without_taxes ?? 0);
                                                    $set('quantity', 1);
                                                    $this->calculateTotal($get, $set);
                                                }

//
                                                $images = is_array($inventory->product->images ?? null)
                                                    ? $inventory->product->images
                                                    : [$inventory->product->images ?? null];

                                                // Si no hay imágenes, asignar una imagen por defecto
                                                if (empty($images) || $images[0] === null) {
                                                    $images = ['products\/noimage.jpg']; // Ruta de la imagen por defecto
                                                }

                                                $set('product_image', $images);


                                            }),

                                        TextInput::make('quantity')
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

                                        TextInput::make('price')
                                            ->label('Costo')
                                            ->step(0.01)
                                            ->numeric()
                                            ->columnSpan(1)
                                            ->required()
                                            ->live()
                                            ->debounce(300)
                                            ->extraAttributes(['onkeyup' => 'this.dispatchEvent(new Event("input"))'])
                                            ->afterStateUpdated(function (callable $get, callable $set) {
                                                $this->calculateTotal($get, $set);
                                            }),


                                        TextInput::make('total')
                                            ->label('Total')
                                            ->step(0.01)
                                            ->readOnly()
                                            ->columnSpan(1)
                                            ->required(),


                                    ])->columnSpan(9)
                                    ->extraAttributes([
                                        'class' => 'bg-blue-100 border border-blue-500 rounded-md p-2',
                                    ])
                                    ->columns(2),


                                Section::make('Image')
                                    ->compact()
                                    ->schema([
                                        FileUpload::make('product_image')
                                            ->label('')
                                            ->previewable(true)
                                            ->openable()
                                            ->storeFiles(false)
                                            ->deletable(false)
                                            ->disabled() // Desactiva el campo
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
//                    ->searchable()
                    ->label('Producto'),
                BooleanColumn::make('inventory.product.is_service')
                    ->label('Producto/Servicio')
                    ->trueIcon('heroicon-o-bug-ant') // Icono cuando `is_service` es true
                    ->falseIcon('heroicon-o-cog-8-tooth') // Icono cuando `is_service` es false

                    ->tooltip(function ($record) {
                        return $record->inventory->product->is_service ? 'Es un servicio' : 'No es un servicio';
                    }),


                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->columnSpan(1),
                TextColumn::make('price')
                    ->label('Precio')
                    ->money('USD', locale: 'en_US')
                    ->columnSpan(1),
                TextColumn::make('discount')
                    ->label('Descuento')
                    ->numeric()
                    ->columnSpan(1),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD', locale: 'en_US')
                    ->columnSpan(1),
            ])
            ->headerActions([
                CreateAction::make()
                    ->modalWidth('7xl')
                    ->modalHeading('Agregar Producto al Traslado')
                    ->label('Agregar Producto')
                    ->after(function (TransferItems $record, Component $livewire) {
                        $this->updateTotalTransfer($record);
                        $livewire->dispatch('refreshTransfer');
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth('7xl')
                    ->after(function (TransferItems $record, Component $livewire) {
                        $this->updateTotalTransfer($record);
                        $livewire->dispatch('refreshTransfer');

                    }),
                DeleteAction::make()
                    ->label('Quitar')
                    ->after(function (TransferItems $record, Component $livewire) {
                        $this->updateTotalTransfer($record);
                        $livewire->dispatch('refreshTransfer');

                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(function (TransferItems $record, Component $livewire) {
                            $selectedRecords = $livewire->getSelectedTableRecords();
                            foreach ($selectedRecords as $record) {
                                $this->updateTotalTransfer($record);
                            }
                            $livewire->dispatch('refreshTransfer');
                        }),

                ]),
            ]);
    }

    protected function calculateTotal(callable $get, callable $set): void
    {
        try {
            $quantity = ($get('quantity') !== "" && $get('quantity') !== null) ? $get('quantity') : 0;
            $price = ($get('price') !== "" && $get('price') !== null) ? $get('price') : 0;
            $total = $quantity * $price;
            $price = round($price, 2);
            $total = round($total, 2);

            $set('price', $price);
            $set('total', $total);
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }


    }

    protected function updateTotalTransfer(TransferItems $record): void
    {
        $transferId = $record->transfer_id;
        $transfer = Transfer::where('id', $transferId)->first();

//        dd($transfer);
        if ($transfer) {
            try {
                $montoTotal = TransferItems::where('transfer_id', $transfer->id)->sum('total') ?? 0;
                $transfer->total = round($montoTotal, 2);
                $transfer->save();
            } catch (Exception $e) {
                Log::error($e->getMessage());
            }
        }
    }
}