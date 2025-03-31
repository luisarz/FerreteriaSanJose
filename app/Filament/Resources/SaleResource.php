<?php

namespace App\Filament\Resources;

use App\Filament\Forms\CreateClienteForm;
use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Models\CashBoxCorrelative;
use App\Models\Customer;
use App\Models\Distrito;
use App\Models\Employee;
use App\Models\HistoryDte;
use App\Models\Inventory;
use App\Models\Municipality;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tribute;
use App\Service\GetCashBoxOpenedService;
use App\Tables\Actions\dteActions;
use Carbon\Carbon;
use EightyNine\FilamentPageAlerts\PageAlert;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

function updateTotalSale(mixed $idItem, array $data): void
{
    $applyRetention = $data['have_retention'] ?? false;
    $applyTax = $data['is_taxed'] ?? false;
    $cash = $data['cash'] ?? false;
    $change = $data['change'] ?? false;
    if ($cash < 0) {

        PageAlert::make()
            ->title('Saved successfully')
            ->body('El monto ingresado no puede ser menor que 0.')
            ->success()
            ->send();
        return;
    }


    $sale = Sale::find($idItem);

    if ($sale) {
        // Fetch tax rates with default values
        $ivaRate = Tribute::where('id', 1)->value('rate') ?? 0;
        $isrRate = Tribute::where('id', 3)->value('rate') ?? 0;

        $ivaRate /= 100;
        $isrRate /= 100;
        // Calculate total and net amounts
        $montoTotal = SaleItem::where('sale_id', $sale->id)->sum('total') ?? 0;
        $neto = $applyTax && $ivaRate > 0 ? $montoTotal / (1 + $ivaRate) : $montoTotal;

        // Calculate tax and retention conditionally
        $iva = $applyTax ? $montoTotal - $neto : 0;
        $retention = $applyRetention ? $neto * $isrRate : 0;

        // Round and save calculated values
        $sale->net_amount = round($neto, 2);
        $sale->taxe = round($iva, 2);
        $sale->retention = round($retention, 2);
        $sale->sale_total = round($montoTotal - $retention, 2);
        $sale->cash = $cash ?? 0;
        $sale->change = $change ?? 0;
        $sale->save();
    }
}

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $label = 'Ventas';
    protected static ?string $navigationGroup = 'Facturación';
    protected static bool $softDelete = true;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')
                    ->schema([

                        Grid::make(12)
                            ->schema([

                                Section::make('Venta')
                                    ->icon('heroicon-o-user')
                                    ->iconColor('success')
                                    ->compact()
                                    ->schema([
                                        Forms\Components\DatePicker::make('operation_date')
                                            ->label('Fecha')
                                            ->required()
                                            ->inlineLabel(true)
                                            ->default(now()),
                                        Forms\Components\Select::make('wherehouse_id')
                                            ->label('Sucursal')
                                            ->debounce(500)
                                            ->relationship('wherehouse', 'name')
                                            ->preload()
                                            ->disabled()
                                            ->default(fn() => optional(Auth::user()->employee)->branch_id), // Null-safe check
                                        Forms\Components\Select::make('document_type_id')
                                            ->label('Comprobante')
//                                            ->relationship('documenttype', 'name')
                                            ->options(function (callable $get) {
                                                $openedCashBox = (new GetCashBoxOpenedService())->getOpenCashBoxId(true);
                                                if ($openedCashBox > 0) {
                                                    return CashBoxCorrelative::with('document_type')
                                                        ->where('cash_box_id', $openedCashBox)
                                                        ->whereIn('document_type_id', [1, 3, 11, 14])
                                                        ->get()
                                                        ->mapWithKeys(function ($item) {
                                                            return [$item->document_type->id => $item->document_type->name];
                                                        })
                                                        ->toArray(); // Asegúrate de devolver un array
                                                }

                                                return []; // Retorna un array vacío si no hay una caja abierta
                                            })
//                                            ->preload()
//                                            ->reactive() // Permite reaccionar a cambios en el campo
//                                            ->afterStateUpdated(function ($state, callable $set) {
//                                                if ($state) {
//                                                    $lastIssuedDocument = CashBoxCorrelative::where('document_type_id', $state)
//                                                        ->first();
//                                                    if ($lastIssuedDocument) {
//                                                        // Establece el número del último documento emitido en otro campo
//                                                        $set('document_internal_number', $lastIssuedDocument->current_number + 1);
//                                                    }
//                                                }
//                                            })
                                            ->required(),
//                                        Forms\Components\TextInput::make('document_internal_number')
//                                            ->label('#   Comprobante')
//                                            ->required()
//                                            ->maxLength(255),


                                        Forms\Components\Select::make('seller_id')
                                            ->label('Vendedor')
                                            ->preload()
                                            ->searchable()
                                            ->debounce(500)
                                            ->options(function (callable $get) {
                                                $wherehouse = $get('wherehouse_id');
                                                $saler = \Auth::user()->employee->id ?? null;
                                                if ($wherehouse) {
                                                    return Employee::where('id', $saler)->pluck('name', 'id');
                                                }
                                                return []; // Return an empty array if no wherehouse selected
                                            })
                                            ->default(fn() => optional(Auth::user()->employee)->id)
                                            ->required()
                                            ->disabled(fn(callable $get) => !$get('wherehouse_id')), // Disable if no wherehouse selected

                                        Forms\Components\Select::make('customer_id')
                                            ->searchable()
                                            ->debounce(500)
                                            ->relationship('customer', 'name')
                                            ->preload()
                                            ->columnSpanFull()
                                            ->inlineLabel(false)
                                            ->getSearchResultsUsing(function (string $query) {
                                                if (strlen($query) < 2) {
                                                    return []; // No buscar si el texto es muy corto
                                                }

                                                // Buscar clientes por múltiples criterios
                                                return (new Customer)->where('name', 'like', "%{$query}%")
                                                    ->orWhere('last_name', 'like', "%{$query}%")
                                                    ->orWhere('nrc', 'like', "%{$query}%")
                                                    ->orWhere('dui', 'like', "%{$query}%")
                                                    ->orWhere('nit', 'like', "%{$query}%")
                                                    ->select(['id', 'name', 'last_name', 'nrc', 'dui', 'nit'])
                                                    ->limit(50)
                                                    ->get()
                                                    ->mapWithKeys(function ($customer) {
                                                        // Formato para mostrar el resultado en el select
                                                        $displayText = "{$customer->name} {$customer->last_name} - NRC: {$customer->nrc} - DUI: {$customer->dui} - NIT: {$customer->nit}";
                                                        return [$customer->id => $displayText];
                                                    });
                                            })
                                            ->getOptionLabelUsing(function ($value) {
                                                // Obtener detalles del cliente seleccionado
                                                $customer = Customer::find($value); // Buscar el cliente por ID
                                                return $customer
                                                    ? "{$customer->name} {$customer->last_name} - NRC: {$customer->nrc} - DUI: {$customer->dui} - NIT: {$customer->nit}"
                                                    : 'Cliente no encontrado';
                                            })
                                            ->label('Cliente')
                                            ->createOptionForm(CreateClienteForm::getForm())
                                            ->createOptionAction(function (Forms\Components\Actions\Action $action) {
                                                return $action
                                                    ->label('Crear cliente')
                                                    ->color('success')
                                                    ->icon('heroicon-o-plus')
                                                    ->modalWidth('7xl');
//                                                    ->size(IconSize::sizeI);
                                            })
                                            ->createOptionUsing(function ($data) {
                                                return Customer::create($data)->id; // Guarda y devuelve el ID del nuevo cliente
                                            }),


                                        Forms\Components\Select::make('sales_payment_status')
                                            ->options(['Pagado' => 'Pagado',
                                                'Pendiente' => 'Pendiente',
                                                'Abono' => 'Abono',])
                                            ->label('Estado de pago')
                                            ->default('Pendiente')
                                            ->hidden()
                                            ->disabled(),
                                        Forms\Components\Select::make('sale_status')
                                            ->options(['Nuevo' => 'Nuevo',
                                                'Procesando' => 'Procesando',
                                                'Cancelado' => 'Cancelado',
                                                'Facturado' => 'Facturado',
                                                'Anulado' => 'Anulado',])
                                            ->default('Nuevo')
                                            ->hidden()
                                            ->required(),
                                        Section::make('')//Resumen Venta
                                        ->description('')
                                            ->compact()
                                            ->schema([
                                                Forms\Components\Placeholder::make('net_amount')
                                                    ->content(fn(?Sale $record) => new HtmlString('<span style="font-weight: bold;  font-size: 15px;">$ ' . number_format($record->net_amount ?? 0, 2) . '</span>'))
                                                    ->inlineLabel()
                                                    ->label('Neto'),

                                                Forms\Components\Placeholder::make('taxe')
                                                    ->content(fn(?Sale $record) => new HtmlString('<span style="font-weight: bold;  font-size: 15px;">$ ' . number_format($record->taxe ?? 0, 2) . '</span>'))
                                                    ->inlineLabel()
                                                    ->label('IVA'),

                                                Forms\Components\Placeholder::make('retention')
                                                    ->content(fn(?Sale $record) => $record->retention ?? 0)
                                                    ->inlineLabel()
                                                    ->content(fn(?Sale $record) => new HtmlString('<span style="font-weight: bold;  font-size: 15px;">$ ' . number_format($record->retention ?? 0, 2) . '</span>'))
                                                    ->label('ISR -1%'),
                                                Forms\Components\Placeholder::make('total')
                                                    ->label('Total')
                                                    ->content(fn(?Sale $record) => new HtmlString('<span style="font-weight: bold; color: red; font-size: 18px;">$ ' . number_format($record->sale_total ?? 0, 2) . '</span>'))
                                                    ->inlineLabel()
                                                    ->extraAttributes(['class' => 'p-0 text-lg']) // Tailwind classes for padding and font size
//                                    ->columnSpan('full'),
                                            ])->columnSpanFull()->columns(4),
                                    ])->columnSpan(9)
                                    ->extraAttributes([
                                        'class' => 'bg-blue-100 border border-blue-500 rounded-md p-2',
                                    ])
                                    ->columns(2),


                                Section::make('Caja')
                                    ->compact()
                                    ->schema([
                                        Select::make('order_id')
                                            ->label('Órdenes')
                                            ->searchable()
                                            ->placeholder('Orden #')
                                            ->preload()
                                            ->debounce(500)
                                            ->getSearchResultsUsing(function (string $searchQuery) {
                                                if (strlen($searchQuery) < 1) {
                                                    return []; // No buscar si el texto es muy corto
                                                }

                                                // Buscar órdenes basadas en el cliente
                                                return Sale::whereHas('customer', function ($customerQuery) use ($searchQuery) {
                                                    $customerQuery->where('name', 'like', "%{$searchQuery}%")
                                                        ->orWhere('last_name', 'like', "%{$searchQuery}%")
                                                        ->orWhere('nrc', 'like', "%{$searchQuery}%")
                                                        ->orWhere('dui', 'like', "%{$searchQuery}%");
                                                })
                                                    ->where('operation_type', 'Order')
                                                    ->orWhere('order_number', 'like', "%{$searchQuery}%")
                                                    ->whereNotIn('sale_status', ['Finalizado', 'Facturada', 'Anulado'])
                                                    ->select(['id', 'order_number', 'operation_type'])
                                                    ->limit(50)
                                                    ->get()
                                                    ->mapWithKeys(function ($sale) {
                                                        // Formato para mostrar el resultado en el select
                                                        $displayText = "Orden # : {$sale->order_number}  - Tipo: {$sale->operation_type}";

                                                        // Incluir el nombre del cliente si es necesario
                                                        if ($sale->customer) {
                                                            $displayText .= " - Cliente: {$sale->customer->name}";
                                                        }

                                                        return [$sale->id => $displayText];
                                                    });
                                            })
                                            ->getOptionLabelUsing(function ($value) {
                                                // Obtener detalles de la orden seleccionada
                                                $sale = Sale::find($value); // Buscar la orden por ID
                                                return $sale
                                                    ? "Orden # : {$sale->order_number} - Cliente: {$sale->customer->name} - Tipo: {$sale->operation_type}"
                                                    : 'Orden no encontrada';
                                            })
                                            ->loadingMessage('Cargando ordenes...')
                                            ->searchingMessage('Buscando Orden...')
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                redirect('admin/sales/' . $state . '/edit');

//                                                return redirect()->route('filament.resources.sales.edit', $state); // 'sales.edit' es la ruta de edición del recurso de "Sale"
                                            }),

                                        Forms\Components\Toggle::make('is_taxed')
                                            ->label('Gravado')
                                            ->default(true)
                                            ->onColor('danger')
                                            ->reactive()
                                            ->offColor('gray')
                                            ->required(),
                                        Forms\Components\Toggle::make('have_retention')
                                            ->label('Retención')
                                            ->onColor('danger')
                                            ->offColor('gray')
                                            ->default(false)
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($set, $state, $get, Component $livewire) {
                                                $idItem = $get('id'); // ID del item de venta
                                                $data = [
                                                    'have_retention' => $state,
                                                    'is_taxed' => $get('is_taxed'),
                                                ];
                                                updateTotalSale($idItem, $data);
                                                $livewire->dispatch('refreshSale');
                                            }),
                                        Forms\Components\Select::make('operation_condition_id')
                                            ->relationship('salescondition', 'name')
                                            ->label('Condición')
                                            ->required()
                                            ->default(1),
                                        Forms\Components\Select::make('payment_method_id')
                                            ->label('F. Pago')
                                            ->relationship('paymentmethod', 'name')
                                            ->preload()
                                            ->searchable()
                                            ->required()
                                            ->default(1),
                                        Forms\Components\TextInput::make('cash')
                                            ->label('Efectivo')
                                            ->required()
                                            ->numeric()
                                            ->default(0.00)
                                            ->live(true)
                                            ->afterStateUpdated(function ($set, $state, $get, Component $livewire, ?Sale $record) {
                                                $sale_total = $record->sale_total;
                                                $cash = $state;

                                                if ($cash < 0) {
                                                    Notification::make()
                                                        ->title('Error')
                                                        ->body('El monto ingresado no puede ser menor que 0.')
                                                        ->danger()
                                                        ->send();
//                                                    $set('cash', 0); // Restablecer el efectivo a 0 en caso de error
//                                                    $set('change', 0); // También establecer el cambio en 0
                                                } elseif ($cash < $sale_total) {
//                                                    $set('cash', number_format($sale_total, 2, '.', '')); // Ajustar el efectivo al total de la venta
//                                                    $set('change', 0); // Sin cambio ya que el efectivo es igual al total
                                                    $set('change', number_format($cash - $sale_total, 2, '.', '')); // Calcular el cambio con formato

                                                } else {
                                                    $set('change', number_format($cash - $sale_total, 2, '.', '')); // Calcular el cambio con formato
                                                }
                                                $idItem = $get('id'); // ID del item de venta
                                                $data = ['cash' => $state, 'change' => $get('change')];
                                                updateTotalSale($idItem, $data);
                                                $livewire->dispatch('refreshSale');

                                            }),
                                        Forms\Components\TextInput::make('change')
                                            ->label('Cambio')
                                            ->required()
                                            ->readOnly()
                                            ->extraAttributes(['class' => 'bg-gray-100 border border-gray-500 rounded-md '])
                                            ->numeric()
                                            ->default(0.00),
                                    ])
                                    ->extraAttributes([
                                        'class' => 'bg-blue-100 border border-blue-500 rounded-md p-2',
                                    ])
                                    ->columnSpan(3)->columns(1),
                            ]),
                    ]),
            ]);
    }

    public static function getTableActions(): array
    {
        return [
            // Eliminar la acción de edición
//            EditAction::make()->hidden(),
        ];
    }

    /**
     * @throws \Exception
     */
    public
    static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Interno')
                    ->numeric()
                    ->searchable()
//                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('wherehouse.name')
                    ->label('Sucursal')
                    ->numeric()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('operation_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->timezone('America/El_Salvador') // Zona horaria (opcional)
                    ->sortable(),

                Tables\Columns\TextColumn::make('documenttype.name')
                    ->label('Tipo')
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_internal_number')
                    ->label('#')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('is_dte')
                    ->label('DTE')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->is_dte && $record->is_hacienda_send) {
                            return 'Enviado';
                        } elseif ($record->is_dte && !$record->is_hacienda_send) {
                            return 'Contingencia (Pendiente)';
                        } else {
                            return 'Sin transmisión';
                        }
                    })
                    ->color(function ($state, $record) {
                        if ($record->is_dte && $record->is_hacienda_send) {
                            return 'success'; // verde
                        } elseif ($record->is_dte && !$record->is_hacienda_send) {
                            return 'warning'; // amarillo
                        } else {
                            return 'danger'; // rojo
                        }
                    })
                    ->tooltip(function ($state, $record) {
                        if ($record->is_dte && $record->is_hacienda_send) {
                            return 'Documento transmitido correctamente a Hacienda';
                        } elseif ($record->is_dte && !$record->is_hacienda_send) {
                            return 'Documento procesado en contingencia, pendiente de enviar a Hacienda';
                        } else {
                            return 'Documento pendiente de transmisión';
                        }
                    }),


//                Tables\Columns\IconColumn::make('is_dte')
//                    ->boolean()
//                    ->tooltip('DTE')
//                    ->trueIcon('heroicon-o-shield-check')
//                    ->falseIcon('heroicon-o-shield-exclamation')
//                    ->label('DTE')
//                    ->sortable(),

                Tables\Columns\BadgeColumn::make('billingModel')
                    ->sortable()
//                    ->searchable()
                    ->label('Facturación')
                    ->tooltip(fn($state) => $state?->id === 2 ? 'Diferido' : 'Previo')
                    ->icon(fn($state) => $state?->id === 2 ? 'heroicon-o-clock' : 'heroicon-o-check-circle')
                    ->color(fn($state) => $state?->id === 2 ? 'danger' : 'success')
                    ->formatStateUsing(fn($state) => $state?->id === 2 ? 'Diferido' : 'Previo'), // Aquí se define el badge


                Tables\Columns\BadgeColumn::make('transmisionType')
                    ->label('Transmisión')
                    ->placeholder('S/N')
                    ->tooltip(fn($state) => $state?->id === 2 ? 'Contingencia' : 'Normal')
                    ->icon(fn($state) => $state?->id === 2 ? 'heroicon-o-clock' : 'heroicon-o-check-circle')
                    ->color(fn($state) => $state?->id === 2 ? 'danger' : 'success')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn($state) => $state?->id === 2 ? 'Contingencia' : 'Normal'), // Texto del badge


                Tables\Columns\TextColumn::make('seller.name')
                    ->label('Vendedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('salescondition.name')
                    ->label('Condición')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_sale_credit')
                    ->label('Credito')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentmethod.name')
                    ->label('Método de pago')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('sales_payment_status')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Pago'),
                Tables\Columns\BadgeColumn::make('sale_status')
                    ->label('Estado')
                    ->extraAttributes(['class' => 'text-lg'])  // Cambia el tamaño de la fuente

                    ->color(fn($record) => $record->sale_status === 'Anulado' ? 'danger' : 'success'),

                Tables\Columns\IconColumn::make('is_taxed')
                    ->label('Gravado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),
                Tables\Columns\TextColumn::make('net_amount')
                    ->label('Neto')
                    ->toggleable()
                    ->money('USD', locale: 'en_US')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('taxe')
                    ->label('IVA')
                    ->toggleable()
                    ->money('USD', locale: 'en_US')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount')
                    ->label('Descuento')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->money('USD', locale: 'en_US')
                    ->sortable(),
                Tables\Columns\TextColumn::make('retention')
                    ->label('Retención')
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->money('USD', locale: 'en_US')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_total')
                    ->label('Total')
                    ->summarize(Sum::make()->label('Total')->money('USD', locale: 'en_US'))
                    ->money('USD', locale: 'en_US')
                    ->sortable(),
                Tables\Columns\TextColumn::make('cash')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('change')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('casher.name')
                    ->label('Cajero')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

//                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(function ($query) {
                $query->where('is_invoiced', true)
                    ->whereIn('operation_type', ['Sale', 'Order', 'Quote'])
                    ->orderby('operation_date', 'desc')
                    ->orderby('document_internal_number', 'desc')
                    ->orderby('is_dte', 'desc');
            })
            ->recordUrl(null)
            ->filters([
                DateRangeFilter::make('operation_date')
                    ->timePicker24()
                    ->startDate(Carbon::now())
                    ->endDate(Carbon::now())
                    ->label('Fecha de venta'),


                Tables\Filters\SelectFilter::make('documenttype')
                    ->label('Documento')
//                    ->multiple()
                    ->preload()
                    ->relationship('documenttype', 'name'),

            ])
            ->actions([
                dteActions::generarDTE(),
                dteActions::imprimirDTE(),
                dteActions::enviarEmailDTE(),
                dteActions::anularDTE(),
                dteActions::historialDTE(),
                Tables\Actions\ReplicateAction::make()
                    ->beforeReplicaSaved(function (Sale $replica, Sale $original) {

                    })
                    ->afterReplicaSaved(function (Sale $replica, Sale $original) {
                        $id = $original->id;
                        $originalPlan = Sale::with('saleDetails')->find($id);
                        foreach ($originalPlan->saleDetails as $details) {
                            $newTask = $details->replicate();
                            $newTask->sale_id = $replica->id; // Set the foreign key to the new plan's id
                            $newTask->push(); // Save the new task

                        }
                    }),

            ], position: ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make('Exportar'),
                ]),
            ]);
    }

    public
    static function getRelations(): array
    {
        return [
            RelationManagers\SaleItemsRelationManager::class,
        ];
    }

    public
    static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
//            'view' => Pages\ViewSale::route('/{record}'),
        ];
    }


}