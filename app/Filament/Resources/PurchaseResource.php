<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Filament\Resources\PurchaseResource\RelationManagers;
use App\Helpers\KardexHelper;
use App\Models\Inventory;
use App\Models\Provider;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tribute;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

function updateTotaPurchase(mixed $idItem, array $data): void
{
    $have_perception = $data['have_perception'] ?? false;
    $retentionPorcentage = 1;

    $purchase = Purchase::find($idItem);
    if ($purchase) {
        // Fetch tax rates with default values
        $ivaRate = Tribute::where('id', 1)->value('rate') ?? 0;
        $isrRate = 1;//Tribute::where('id', 3)->value('rate') ?? 0;

        $ivaRate /= 100;
        $isrRate /= 100;
        // Calculate total and net amounts
        $montoTotal = PurchaseItem::where('purchase_id', $purchase->id)->sum('total') ?? 0;
        // Calculate tax and retention conditionally
        $iva =$montoTotal * 0.13 ;
        $perception = $have_perception ? $montoTotal * $isrRate : 0;

        // Round and save calculated values
        $purchase->net_value = round($montoTotal, 2);
        $purchase->taxe_value = round($iva, 2);
        $purchase->perception_value = round($perception, 2);
        $purchase->purchase_total = round($montoTotal + $perception+$iva, 2);
        $purchase->save();
    }
}

class PurchaseResource extends Resource
{
    protected static ?string $model = Purchase::class;
    protected static ?string $label = 'Compras';
    protected static ?string $navigationGroup = 'Inventario';

//    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                    ->schema([
                        Forms\Components\Section::make('COMPRA')
//                            ->description('Informacion general de la compra')
                            ->icon('heroicon-o-book-open')
                            ->iconColor('danger')
                            ->compact()
                            ->schema([
                                Forms\Components\Select::make('provider_id')
                                    ->relationship('provider', 'comercial_name')
                                    ->label('Proveedor')
                                    ->preload()
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('employee_id')
                                    ->relationship('employee', 'name')
                                    ->label('Empleado')
                                    ->preload()
                                    ->default(fn () => optional(\Auth::user()->employee)->id ?? '')
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::make('wherehouse_id')
                                    ->label('Sucursal')
                                    ->relationship('wherehouse', 'name')
                                    ->default(fn() => \Auth::user()->employee->branch_id)
                                    ->preload()
                                    ->required(),
                                Forms\Components\DatePicker::make('purchase_date')
                                    ->label('Fecha')
                                    ->inlineLabel()
                                    ->default(today())
                                    ->required(),
                                Forms\Components\Select::make('document_type')
                                    ->label('Tipo Documento')
                                    ->options([
                                        'Electrónico' => 'Electrónico',
                                        'Físico' => 'Físico',
                                    ])
                                    ->default('Físico')
                                    ->required()
                                    ->reactive() // Makes the select field reactive to detect changes
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state === 'Electrónico') {
                                            $set('document_number_label', 'DTE');
                                        } else {
                                            $set('document_number_label', 'Número CCF');
                                        }
                                    }),

                                Forms\Components\TextInput::make('document_number')
                                    ->label(fn(callable $get) => $get('document_number_label') ?? 'Número CCF') // Default label if not set
                                    ->required()
                                    ->maxLength(255),


                                Forms\Components\Select::make('pruchase_condition')
                                    ->label('Condición')
                                    ->options([
                                        'Contado' => 'Contado',
                                        'Crédito' => 'Crédito',
                                    ])
                                    ->default('Contado')
                                    ->required()
                                    ->live() // Hace el campo reactivo para detectar cambios
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        // Cuando se selecciona "Contado", realiza los siguientes cambios:
                                        if ($state === 'Contado') {
                                            $set('credit_days', null); // Vacia el campo de días de crédito
                                            $set('paid', true); // Marca como pagado
                                            $set('status', 'Finalizado'); // Establece el estado a "Finalizado"
                                        } else {
                                            // Cuando se selecciona "Crédito", realiza los siguientes cambios:
                                            $set('paid', false); // Marca como no pagado
                                            $set('status', 'Procesando'); // Establece el estado a "Procesando"
                                        }
                                    }),

                                Forms\Components\TextInput::make('credit_days')
                                    ->label('Días de Crédito')
                                    ->numeric()
                                    ->default(null)
                                    ->visible(fn(callable $get) => $get('pruchase_condition') != 'Contado') // Solo visible cuando se selecciona "Crédito"
                                    ->required(fn(callable $get) => $get('pruchase_condition') != 'Contado'), // Obligatorio solo si "Crédito" es seleccionado

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'Procesando' => 'Procesando',
                                        'Finalizado' => 'Finalizado',
                                        'Anulado' => 'Anulado',
                                    ])
                                    ->default('Procesando') // Establece "Procesando" como valor predeterminado
                                    ->required(),


                            ])->columnSpan(3)->columns(2),
                        Forms\Components\Section::make('Total')
                            ->compact()
                            ->icon('heroicon-o-currency-dollar')
                            ->iconColor('success')
                            ->schema([
                                Forms\Components\Toggle::make('have_perception')
                                    ->label('Percepción')
                                    ->live()
                                    ->required()
                                    ->afterStateUpdated(function ($set, $state, $get, Component $livewire) {
                                        $idItem = $get('id'); // ID del item de venta
                                        $data = [
                                            'have_perception' => $state,
                                        ];
                                        updateTotaPurchase($idItem, $data);
                                        $livewire->dispatch('refreshPurchase');
                                    }),
                                Forms\Components\Placeholder::make('net_value')
                                    ->content(function (?Purchase $record) {
                                        return $record ? ($record->net_value ?? 0) : 0;
                                    })
                                    ->inlineLabel()
                                    ->label('Neto'),

                                Forms\Components\Placeholder::make('taxe_value')
                                    ->content(function (?Purchase $record) {
                                        return $record ? ($record->taxe_value ?? 0) : 0;
                                    })
                                    ->inlineLabel()
                                    ->label('IVA'),

                                Forms\Components\Placeholder::make('perception_value')
                                    ->content(fn(?Purchase $record) => $record->perception_value ?? 0)
                                    ->inlineLabel()
                                    ->label('Percepción:'),

                                Forms\Components\Placeholder::make('purchase_total')
                                    ->label('Total')
                                    ->content(fn(?Purchase $record) => new HtmlString('<span style="font-weight: bold; color: red; font-size: 18px;">$ ' . number_format($record->purchase_total ?? 0, 2) . '</span>'))
                                    ->inlineLabel()
                                    ->extraAttributes(['class' => 'p-0 text-lg']) // Tailwind classes for padding and font size
                                    ->columnSpan('full'),
                            ])->
                            columnSpan(1),
                    ])->columns(4),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider.comercial_name')
                    ->label('Proveedor')
                    ->sortable(),
                Tables\Columns\TextColumn::make('employ.name')
                    ->label('Empleado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('wherehouse.name')
                    ->label('Sucursal')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_type')
                    ->label('Documento')
                ,
                Tables\Columns\TextColumn::make('document_number')
                    ->label('#')
                    ->searchable(),
                Tables\Columns\TextColumn::make('pruchase_condition')
                    ->label('Cond. Compra'),
                Tables\Columns\TextColumn::make('credit_days')
                    ->label('Crédito')
                    ->placeholder('Contado')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                ->badge()
                ->label('Estado')
                    ->color(fn($record) => match ($record->status) {
                        'Anulado' => 'danger',
                        'Procesando' => 'warning',
                        'Finalizado' => 'success',
                        default => 'gray',
                    })

                ,
                Tables\Columns\IconColumn::make('have_perception')
                    ->label('Percepción')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),
                Tables\Columns\TextColumn::make('net_value')
                    ->label('NETO')
                    ->money('USD', true, 'en_US')
                    ->sortable(),
                Tables\Columns\TextColumn::make('taxe_value')
                    ->label('IVA')
                    ->money('USD', true, 'en_US')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('perception_value')
                    ->label('Percepción')
                    ->money('USD', true, 'en_US')
                    ->sortable(),
                Tables\Columns\TextColumn::make('purchase_total')
                    ->label('Total')
                    ->money('USD', true, 'en_US')
                    ->sortable(),
                Tables\Columns\IconColumn::make('paid')
                    ->label('Pagada')
                    ->boolean(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(function ($record) {
                return self::getUrl('purchase',
                    [
                        'record' => $record->id
                    ]);
            })
            ->modifyQueryUsing(function ($query) {
                $query->where('process_document_type','=','Compra');
            })
            ->filters([
                DateRangeFilter::make('purchase_date')
                    ->timePicker24()
                    ->startDate(Carbon::now())
                    ->endDate(Carbon::now())
                    ->label('Fecha de Compra'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make('ver compra')
                    ->modal()
                    ->modalHeading('Ver Compra')
                    ->modalWidth('6xl'),

                Tables\Actions\Action::make('Anular')->label('Anular')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->icon('heroicon-o-trash')
                    ->hidden(fn($record) => $record->status === 'Anulado')
                    ->action(function (Purchase $purchase) {
                    $purchaseItems = PurchaseItem::where('purchase_id', $purchase->id)->get();
                    $provider = Provider::with('pais')->find($purchase->provider_id);
                    $entity = $provider->comercial_name;
                    $pais = $provider->pais->name;

                    foreach ($purchaseItems as $item) {
                        $inventory = Inventory::find($item->inventory_id);

                        // Verifica si el inventario existe
                        if (!$inventory) {
                            \Log::error("Inventario no encontrado para el item de compra: {$item->id}");
                            continue; // Si no se encuentra el inventario, continua con el siguiente item
                        }

                        // Actualiza el stock del inventario
                        $newStock = $inventory->stock - $item->quantity;
                        $inventory->update(['stock' => $newStock,"cost_without_taxes"=>$item->price]);

                        // Crear el Kardex
                        $kardex = KardexHelper::createKardexFromInventory(
                            $inventory->branch_id, // Se pasa solo el valor de branch_id (entero)
                            now(), // date
                            'Anulacion', // operation_type
                            $purchase->id, // operation_id

                            $item->id, // operation_detail_id
                            'ANULACION -CCF', // document_type
                            $purchase->document_number, // document_number
                            $entity, // entity
                            $pais, // nationality
                            $inventory->id, // inventory_id
                            $inventory->stock + $item->quantity, // previous_stock
                            0, // stock_in
                            $item->quantity, // stock_out
                            $inventory->stock, // stock_actual
                            $item->quantity * $item->price, // money_in
                            0, // money_out
                            $inventory->stock * $item->price, // money_actual
                            0, // sale_price
                            $item->price // purchase_price
                        );

                        // Verifica si la creación del Kardex fue exitosa
                        if (!$kardex) {
                            \Log::error("Error al crear Kardex para el item de compra: {$item->id}");
                        }
                        $purchase->update(['status' =>"Anulado"]);
                        Notification::make('Anulacion de compra')
                            ->title('Compra anulada de manera existosa')
                            ->body('La compra fue anulada de manera existosa')
                            ->success()
                            ->send();
                    }
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PurchaseItemsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPurchases::route('/'),
            'create' => Pages\CreatePurchase::route('/create'),
            'edit' => Pages\EditPurchase::route('/{record}/edit'),
            'purchase' => Pages\ViewPurchase::route('/{record}/sale'),
        ];
    }

}
