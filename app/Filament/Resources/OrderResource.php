<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Sale;
use App\Tables\Actions\dteActions;
use App\Tables\Actions\orderActions;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;

class OrderResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $label = 'Ordenes';
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

                                Section::make('Orden de Trabajo-Venta')
                                    ->icon('heroicon-o-user')
                                    ->iconColor('success')
                                    ->compact()
                                    ->schema([
                                        Forms\Components\DatePicker::make('operation_date')
                                            ->label('Fecha')
                                            ->required()
                                            ->inlineLabel(true)
                                            ->default(now()),

                                        Forms\Components\Select::make('seller_id')
                                            ->label('Vendedor')
                                            ->preload()
                                            ->searchable()
                                            ->live()
                                            ->options(function (callable $get) {
                                                $wherehouse = $get('wherehouse_id');
                                                if ($wherehouse) {
                                                    return Employee::where('branch_id', $wherehouse)->pluck('name', 'id');
                                                }
                                                return []; // Return an empty array if no wherehouse selected
                                            })
                                            ->default(fn() => optional(Auth::user()->employee)->id)
                                            ->required()
                                            ->disabled(fn(callable $get) => !$get('wherehouse_id')), // Disable if no wherehouse selected
                                        Forms\Components\Select::make('customer_id')
                                            ->searchable()
                                            ->live()
//                                            ->inlineLabel(false)
//                                            ->columnSpanFull()
                                            ->preload()
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
                                            ->createOptionForm([
                                                Section::make('Nuevo Cliente')
                                                    ->schema([
                                                        Select::make('wherehouse_id')
                                                            ->label('Sucursal')
                                                            ->inlineLabel(false)
                                                            ->options(function (callable $get) {
                                                                $wherehouse = (Auth::user()->employee)->branch_id;
                                                                if ($wherehouse) {
                                                                    return \App\Models\Branch::where('id', $wherehouse)->pluck('name', 'id');
                                                                }
                                                                return []; // Return an empty array if no wherehouse selected
                                                            })
                                                            ->preload()
                                                            ->default(fn() => optional(Auth::user()->employee)->branch_id)
                                                            ->columnSpanFull(),
                                                        Forms\Components\TextInput::make('name')
                                                            ->required()
                                                            ->label('Nombre'),
                                                        Forms\Components\TextInput::make('last_name')
                                                            ->required()
                                                            ->label('Apellido'),
                                                    ])->columns(2),
                                            ])
                                            ->createOptionUsing(function ($data) {
                                                return Customer::create($data)->id; // Guarda y devuelve el ID del nuevo cliente
                                            })
                                        ,


                                        Forms\Components\Select::make('sales_payment_status')
                                            ->options(['Pagado' => 'Pagado',
                                                'Pendiente' => 'Pendiente',
                                                'Abono' => 'Abono',])
                                            ->label('Estado de pago')
                                            ->default('Pendiente')
                                            ->hidden()
                                            ->disabled(),

                                    ])->columnSpan(9)
                                    ->extraAttributes(['class' => 'bg-blue-100 border border-blue-500 rounded-md p-2'])
                                    ->columns(2),

//                                Section::make('Orden Total' . ($this->getOrderNumber() ?? 'Sin número'))
                                Section::make('')
                                    ->compact()
                                    ->schema([
                                        Forms\Components\Placeholder::make('Orden')
                                            ->label('Orden #')
                                            ->content(fn(?Sale $record) => new HtmlString(
                                                '<span style="font-weight: 600; color: #FFFFFF; font-size: 16px; background-color: #0056b3; padding: 4px 8px; border-radius: 5px; display: inline-block;">'
                                                . ($record->order_number ?? '-') .
                                                '</span>'
                                            ))
                                            ->inlineLabel()
                                            ->extraAttributes(['class' => 'p-0 text-lg']), // Tailwind classes for padding and font size
                                        Select::make('wherehouse_id')
                                            ->label('Sucursal')
                                            ->inlineLabel(true)
                                            ->relationship('wherehouse', 'name')
                                            ->preload()
                                            ->default(fn() => optional(Auth::user()->employee)->branch_id)
                                            ->columnSpanFull(),

                                        Forms\Components\Placeholder::make('total')
                                            ->label('Total')
                                            ->content(fn(?Sale $record) => new HtmlString('<span style="font-weight: bold; color: red; font-size: 18px;">$ ' . number_format($record->sale_total ?? 0, 2) . '</span>'))
                                            ->inlineLabel()
                                            ->extraAttributes(['class' => 'p-0 text-lg']) // Tailwind classes for padding and font size
                                    ])
                                    ->extraAttributes([
                                        'class' => 'bg-blue-100 border border-blue-500 rounded-md p-2',
                                    ])
                                    ->columnSpan(3)->columns(1),
                            ]),
                    ]),
            ]);
    }


    public
    static function table(Table $table): Table
    {
        return $table
            ->columns([


                Tables\Columns\TextColumn::make('order_number')
                    ->label('Orden')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('wherehouse.name')
                    ->label('Sucursal')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\columns\TextColumn::make('operation_date')
                    ->label('Fecha')
                    ->date('d-m-Y')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_invoiced')
                    ->boolean()
                    ->tooltip('Facturada')
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-lock-open')
                    ->label('Procesada')
                    ->sortable(),
                Tables\Columns\TextColumn::make('wherehouse.name')
                    ->label('Sucursal')
                    ->numeric()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('seller.name')
                    ->label('Vendedor')
                    ->searchable()
                    ->sortable(),


                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_status')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn($state, $record) => $record->deleted_at ? 'Eliminado' : $state)
                    ->color(fn($state) => match ($state) {
                        'Nueva', 'En proceso' => 'info',
                        'Finalizado' => 'success',
                        'Pendiente' => 'warning',
                        'Anulado', 'Eliminado', 'Cancelada' => 'danger',
                        default => null, // Sin color
                    })
                    ->label('Estado'),


                Tables\Columns\TextColumn::make('retention')
                    ->label('Retención')
                    ->toggleable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->money('USD', locale: 'en_US')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_total')
                    ->label('Total')
                    ->money('USD', locale: 'en_US')
                    ->summarize(Sum::make()->label('Total')->money('USD', locale: 'en_US'))
//                    ->summarize(
//                        Sum::make()
//                            ->using(fn (Summarizer $summarizer) => $summarizer
//                                ->query(fn ($query) => $query->where('sale_status', '!=', 'Anulado','Eliminado') // Exclude canceled or deleted orders)
//                                )
//                            )
//                            ->label('Total')
//                            ->money('USD', locale: 'en_US')
//                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('Descuento')
                    ->suffix('%')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_order_after_discount')
                    ->label('Total - Descuento')
//                    ->toggleable(isToggledHiddenByDefault: true)
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

                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Modificación')
                    ->dateTime()
                    ->sortable()
//                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordUrl(null)
            ->filters([
                DateRangeFilter::make('operation_date')
                    ->timePicker24()
                    ->startDate(Carbon::now())
                    ->endDate(Carbon::now()),
                Tables\Filters\TrashedFilter::make('eliminados')
                    ->label('Eliminados')
                    ->query(fn($query) => $query->withoutGlobalScope(SoftDeletingScope::class))
                    ->default(true),

            ])
            ->persistFiltersInSession()
            ->actions([
                orderActions::printOrder(),
                Tables\Actions\EditAction::make()->label('')->iconSize(IconSize::Large)->color('warning')
                    ->visible(fn($record) => $record->sale_status == 'Nueva' && $record->deleted_at == null
                    ),
                Tables\Actions\DeleteAction::make('delete')
                    ->iconSize(IconSize::Large)
                    ->visible(fn($record) => !in_array($record->sale_status, ['Finalizado', 'Facturada', 'Anulado']) && $record->deleted_at == null)
                    ->label(''),
                orderActions::billingOrden(),
                orderActions::cancelOrder(),
                Tables\Actions\RestoreAction::make()->label('')->iconSize(IconSize::Large)->color('success'),
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public function getOrderTitle(): string
    {
        return 'Orden Total - ' . ($this->record?->order_number ?? 'Sin número');
    }

}