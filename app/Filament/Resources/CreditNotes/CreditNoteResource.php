<?php


namespace App\Filament\Resources\CreditNotes;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\CreditNotes\RelationManagers\CNtemsRelationManager;
use App\Filament\Resources\CreditNotes\Pages\ListCreditNotes;
use App\Filament\Resources\CreditNotes\Pages\CreateCreditNote;
use App\Filament\Resources\CreditNotes\Pages\EditCreditNote;
use Exception;
use App\Filament\Resources\CreditNoteResource\Pages;
use App\Filament\Resources\CreditNoteResource\RelationManagers;
use App\Models\CashBoxCorrelative;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\HistoryDte;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Tribute;
use App\Service\GetCashBoxOpenedService;
use App\Tables\Actions\creditNotesActions;
use App\Tables\Actions\dteActions;
use Carbon\Carbon;
use EightyNine\FilamentPageAlerts\PageAlert;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
// Exportación nativa de Filament 4
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;

function updateTotalNC(mixed $idItem, array $data): void
{
    $applyRetention = $data['have_retention'] ?? false;
    $applyTax = $data['is_taxed'] ?? false;
    $cash = $data['cash'] ?? false;
    $change = $data['change'] ?? false;
    if ($cash < 0) {

        Notification::make()
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

class CreditNoteResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $label = 'Notas';
    protected static string | \UnitEnum | null $navigationGroup = 'Facturación';
    protected static bool $softDelete = true;


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                    ->schema([

                        Grid::make(12)
                            ->schema([

                                Section::make('Nota de Crédito')
                                    ->icon('heroicon-o-user')
                                    ->iconColor('success')
                                    ->compact()
                                    ->schema([
                                        DatePicker::make('operation_date')
                                            ->label('Fecha')
                                            ->required()
                                            ->inlineLabel(true)
                                            ->default(now()),
                                        Select::make('wherehouse_id')
                                            ->label('Sucursal')
                                            ->debounce(500)
                                            ->relationship('wherehouse', 'name')
                                            ->preload()
                                            ->disabled()
                                            ->default(fn() => optional(Auth::user()->employee)->branch_id), // Null-safe check
                                        Select::make('document_type_id')
                                            ->label('Comprobante')
                                            ->default(4)
                                            ->reactive()
                                            ->options(function (callable $get) {
                                                $openedCashBox = (new GetCashBoxOpenedService())->getOpenCashBox();
                                                if ($openedCashBox > 0) {
                                                    return CashBoxCorrelative::with('document_type')
                                                        ->where('cash_box_id', $openedCashBox['id_caja'])
                                                        ->whereIn('document_type_id', [4,5,6])
                                                        ->get()
                                                        ->mapWithKeys(function ($item) {
                                                            return [$item->document_type->id => $item->document_type->name];
                                                        })
                                                        ->toArray(); // Asegúrate de devolver un array
                                                }

                                                return []; // Retorna un array vacío si no hay una caja abierta
                                            })
//
                                            ->required(),
//


                                        Select::make('seller_id')
                                            ->label('Encargado')
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

                                        Select::make('customer_id')
                                            ->searchable()
                                            ->debounce(500)
                                            ->preload()
                                            ->required()
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
                                            ->label('Cliente'),

                                        Select::make('document_related_id')
                                            ->label('V. relacionacionada')
                                            ->searchable()
//                                            ->required()
                                            ->inlineLabel(false)
                                            ->placeholder('Venta #')
                                            ->preload()
                                            ->required(fn (callable $get) => in_array($get('document_type_id'), [5,6])) // requerido si es Nota de crédito o débito

                                            ->debounce(500)
                                            ->getSearchResultsUsing(function (string $searchQuery) {
                                                if (strlen($searchQuery) < 1) {
                                                    return []; // No buscar si el texto es muy corto
                                                }
                                                // Buscar órdenes basadas en el cliente
                                                return Sale::where(function ($query) use ($searchQuery) {
                                                    $query->whereHas('customer', function ($customerQuery) use ($searchQuery) {
                                                        $customerQuery->where('name', 'like', "%{$searchQuery}%")
                                                            ->orWhere('last_name', 'like', "%{$searchQuery}%")
                                                            ->orWhere('nrc', 'like', "%{$searchQuery}%")
                                                            ->orWhere('dui', 'like', "%{$searchQuery}%");
                                                    })
                                                        ->orWhere('document_internal_number', 'like', "%{$searchQuery}%");
                                                })
                                                    ->whereIn('operation_type', ['Sale', 'Order', 'Quote'])
                                                    ->whereIn('document_type_id', [3])
                                                    ->whereIn('sale_status', ['Finalizado', 'Facturada', 'Anulado'])
                                                    ->select(['id', 'document_internal_number', 'document_type_id', 'operation_type', 'customer_id'])
                                                    ->limit(50)
                                                    ->get()
                                                    ->mapWithKeys(function ($sale) {
                                                        $operationType = $sale->document_type_id == 3 ? 'CCF' : 'NC';
                                                        $displayText = "  {$sale->document_internal_number}  - $operationType";
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
                                                    ? "Venta # : {$sale->document_internal_number} - Cliente: {$sale->customer->name} - Tipo: {$sale->operation_type}"
                                                    : 'Orden no encontrada';
                                            })
                                            ->loadingMessage('Cargando Documentos...')
                                            ->searchingMessage('Buscando documentos...')
                                        ,


                                        Select::make('sales_payment_status')
                                            ->options(['Pagado' => 'Pagado',
                                                'Pendiente' => 'Pendiente',
                                                'Abono' => 'Abono',])
                                            ->label('Estado de pago')
                                            ->default('Pendiente')
                                            ->hidden()
                                            ->disabled(),
                                        Select::make('sale_status')
                                            ->options(['Nuevo' => 'Nuevo',
                                                'Procesando' => 'Procesando',
                                                'Cancelado' => 'Cancelado',
                                                'Facturado' => 'Facturado',
                                                'Anulado' => 'Anulado',])
                                            ->default('Nuevo')
                                            ->hidden()
                                            ->required(),

                                    ])->columnSpan(9)
                                    ->extraAttributes([
                                        'class' => 'bg-blue-100 border border-blue-500 rounded-md p-2',
                                    ])
                                    ->columns(2),


                                Section::make('Caja')
                                    ->compact()
                                    ->schema([


                                        Placeholder::make('net_amount')
                                            ->content(fn(?Sale $record) => new HtmlString('<span style="font-weight: bold;  font-size: 15px;">$ ' . number_format($record->net_amount ?? 0, 2) . '</span>'))
                                            ->inlineLabel()
                                            ->label('Neto'),
                                        Placeholder::make('taxe')
                                            ->content(fn(?Sale $record) => new HtmlString('<span style="font-weight: bold;  font-size: 15px;">$ ' . number_format($record->taxe ?? 0, 2) . '</span>'))
                                            ->inlineLabel()
                                            ->label('IVA'),
                                        Placeholder::make('total')
                                            ->label('Total')
                                            ->content(fn(?Sale $record) => new HtmlString('<span style="font-weight: bold; color: red; font-size: 18px;">$ ' . number_format($record->sale_total ?? 0, 2) . '</span>'))
                                            ->inlineLabel()
                                            ->extraAttributes(['class' => 'p-0 text-lg']),

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
     * @throws Exception
     */
    public
    static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('wherehouse.name')
                    ->label('Sucursal')
                    ->numeric()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('operation_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->timezone('America/El_Salvador') // Zona horaria (opcional)
                    ->sortable(),

                TextColumn::make('documenttype.name')
                    ->label('Tipo')
                    ->sortable(),
                TextColumn::make('document_internal_number')
                    ->label('#')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                BadgeColumn::make('is_dte')
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

                BadgeColumn::make('billingModel')
                    ->sortable()
//                    ->searchable()
                    ->label('Facturación')
                    ->tooltip(fn($state) => $state?->id === 2 ? 'Diferido' : 'Previo')
                    ->icon(fn($state) => $state?->id === 2 ? 'heroicon-o-clock' : 'heroicon-o-check-circle')
                    ->color(fn($state) => $state?->id === 2 ? 'danger' : 'success')
                    ->formatStateUsing(fn($state) => $state?->id === 2 ? 'Diferido' : 'Previo'), // Aquí se define el badge


                BadgeColumn::make('transmisionType')
                    ->label('Transmisión')
                    ->placeholder('S/N')
                    ->tooltip(fn($state) => $state?->id === 2 ? 'Contingencia' : 'Normal')
                    ->icon(fn($state) => $state?->id === 2 ? 'heroicon-o-clock' : 'heroicon-o-check-circle')
                    ->color(fn($state) => $state?->id === 2 ? 'danger' : 'success')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn($state) => $state?->id === 2 ? 'Contingencia' : 'Normal'), // Texto del badge


                TextColumn::make('seller.name')
                    ->label('Encargado')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
//                Tables\Columns\TextColumn::make('salescondition.name')
//                    ->label('Condición')
//                    ->sortable(),
//                Tables\Columns\TextColumn::make('status_sale_credit')
//                    ->label('Credito')
//                    ->toggleable(isToggledHiddenByDefault: true)
//                    ->sortable(),
//                Tables\Columns\TextColumn::make('paymentmethod.name')
//                    ->label('Método de pago')
//                    ->toggleable(isToggledHiddenByDefault: true)
//                    ->sortable(),
                TextColumn::make('sales_payment_status')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Pago'),
                BadgeColumn::make('sale_status')
                    ->label('Estado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->extraAttributes(['class' => 'text-lg'])  // Cambia el tamaño de la fuente
                    ->color(fn($record) => $record->sale_status === 'Anulado' ? 'danger' : 'success'),

                IconColumn::make('is_taxed')
                    ->label('Gravado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),
                TextColumn::make('net_amount')
                    ->label('Neto')
                    ->toggleable()
                    ->money('USD', locale: 'en_US')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('taxe')
                    ->label('IVA')
                    ->toggleable()
                    ->money('USD', locale: 'en_US')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('discount')
                    ->label('Descuento')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->money('USD', locale: 'en_US')
                    ->sortable(),
                TextColumn::make('retention')
                    ->label('Retención')
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->money('USD', locale: 'en_US')
                    ->sortable(),
                TextColumn::make('sale_total')
                    ->label('Total')
                    ->summarize(Sum::make()->label('Total')->money('USD', locale: 'en_US'))
                    ->money('USD', locale: 'en_US')
                    ->sortable(),
                TextColumn::make('cash')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('change')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
//                Tables\Columns\TextColumn::make('casher.name')
//                    ->label('Cajero')
//                    ->toggleable(isToggledHiddenByDefault: true)
//                    ->sortable(),
            ])
            ->modifyQueryUsing(function ($query) {
                $query->where('is_invoiced', true)
                    ->whereIn('operation_type', ['NC','ND','NR'])
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


                SelectFilter::make('documenttype')
                    ->label('Sucursal')
//                    ->multiple()
                    ->preload()
                    ->relationship('documenttype', 'name'),

            ])
            ->recordActions([
                creditNotesActions::generarDTE(),
                creditNotesActions::imprimirDTE(),
                creditNotesActions::enviarEmailDTE(),
                creditNotesActions::anularDTE(),
                creditNotesActions::historialDTE(),

            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    // TODO: Implementar exportador para notas de crédito
                    // ExportAction requiere un Exporter class
                ]),
            ]);
    }

    public
    static function getRelations(): array
    {
        return [
            CNtemsRelationManager::class,
        ];
    }

    public
    static function getPages(): array
    {
        return [
            'index' => ListCreditNotes::route('/'),
            'create' => CreateCreditNote::route('/create'),
            'edit' => EditCreditNote::route('/{record}/edit'),
        ];
    }


}