<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashboxOpenResource\Pages;
use App\Filament\Resources\CashboxOpenResource\RelationManagers;
use App\Models\CashBoxOpen;
use App\Models\Employee;
use App\Models\Sale;
use App\Service\GetCashBoxOpenedService;
use App\Traits\Traits\GetOpenCashBox;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class CashboxOpenResource extends Resource
{
    protected static ?string $model = CashBoxOpen::class;

//    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static ?string $label = "Apertura de Cajas";
    public static ?string $navigationGroup = 'Facturación';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                    ->compact()
                    ->columnSpan(2)
                    ->label('Administracion Aperturas de caja')
                    ->schema([
                        Forms\Components\Section::make('Datos de apertura')
                            ->compact()
                            ->icon('heroicon-o-shopping-cart')
                            ->iconColor('success')
                            ->schema([
                                Select::make('cashbox_id')
                                    ->relationship('cashbox', 'description')
                                    ->options(function () {
                                        $whereHouse = auth()->user()->employee->branch_id;
                                        return \App\Models\CashBox::where('branch_id', $whereHouse)
                                            ->where('is_open', '0')
                                            ->get()
                                            ->pluck('description', 'id');
                                    })
                                    ->disabled(function (?CashBoxOpen $record) {
                                        return $record !== null;
                                    })
                                    ->label('Caja')
                                    ->preload()
                                    ->searchable()
                                    ->required(),
                                Forms\Components\Select::class::make('open_employee_id')
                                    ->relationship('openEmployee', 'name', function ($query) {
                                        $whereHouse = auth()->user()->employee->branch_id;
                                        $query->where('branch_id', $whereHouse);
                                    })
                                    ->default(auth()->user()->employee->id)
                                    ->visible(function (?CashBoxOpen $record = null) {
                                        return $record === null;

                                    })
                                    ->label('Empleado Apertura')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\DateTimePicker::make('opened_at')
                                    ->label('Fecha de apertura')
                                    ->inlineLabel(true)
                                    ->default(now())
                                    ->visible(function (?CashBoxOpen $record = null) {
                                        return $record === null;

                                    })
                                    ->required(),
                                TextInput::make('open_amount')
                                    ->label('Monto Apertura')
                                    ->required()
                                    ->numeric()
                                    ->disabled(function (?CashBoxOpen $record) {
                                        return $record !== null;
                                    })
                                    ->label('Monto Apertura'),
                            ])->columns(2)
                        ,
                        Forms\Components\Section::make('')
                            ->hidden(function (?CashBoxOpen $record = null) {
                                if ($record === null) {
                                    return true;
                                }
                            })
                            ->schema([
                                Forms\Components\Section::make('Ingresos')
                                    ->extraAttributes(['class' => 'border-r border-gray-200'])
                                    ->schema([
                                        Forms\Components\Placeholder::make('saled_amount')
                                            ->label('Facturación')
                                            ->inlineLabel(true)
                                            ->content(function () {
                                                $openedCashBox = (new GetCashBoxOpenedService())->getTotal(false);
                                                return new HtmlString('<span style="font-weight: bold; font-size: 15px;">$ ' . number_format($openedCashBox, 2) . '</span>');
                                            }),
                                        Forms\Components\Placeholder::make('ordered_amount')
                                            ->label('Ordenes')
                                            ->inlineLabel(true)
                                            ->content(function () {
                                                $openedCashBox = (new GetCashBoxOpenedService())->getTotal(true, true);
                                                return new HtmlString('<span style="font-weight: bold; font-size: 15px;">$ ' . number_format($openedCashBox, 2) . '</span>');
                                            }),

                                        Forms\Components\Placeholder::make('in_cash_amount')
                                            ->label('Caja Chica')
                                            ->inlineLabel(true)
                                            ->content(function () {
                                                $smalCashBoxIngresoTotal = (new GetCashBoxOpenedService())->minimalCashBoxTotal('Ingreso');
                                                return new HtmlString('<span style="font-weight: bold; font-size: 15px;">$ ' . number_format($smalCashBoxIngresoTotal, 2) . '</span>');
                                            }),
                                    ])->columnSpan(1),
                                Forms\Components\Section::make('Egresos')
                                    ->schema([
                                        Forms\Components\Placeholder::make('out_cash_amount')
                                            ->label('Caja Chica')
                                            ->inlineLabel(true)
                                            ->content(function () {
                                                $smalCashBoxEgresoTotal = (new GetCashBoxOpenedService())->minimalCashBoxTotal('Egreso');
                                                return new HtmlString('<span style="font-weight: bold; font-size: 15px;">$ ' . number_format($smalCashBoxEgresoTotal, 2) . '</span>');
                                            }),
                                    ])->columnSpan(1),




                            ])->columns(2)
                        ,
                        Forms\Components\Section::make('Cierre')
                            ->hidden(function (?CashBoxOpen $record = null) {
                                if ($record === null) {
                                    return true;
                                }
                            })
                        ->schema([
                            Forms\Components\DateTimePicker::make('closed_at')
                                ->label('Fecha de cierre')
                                ->required()
                                ->default(now())
                                ->hidden(function (?CashBoxOpen $record = null) {
                                    return $record === null;
                                })
                                ->inlineLabel(true),

                            Forms\Components\Placeholder::make('closed_amount')
                                ->label('Monto Cierre')
                                ->inlineLabel(true)
                                ->content(function (callable $get) {
                                    $totalInresos = (new GetCashBoxOpenedService())->minimalCashBoxTotal('Ingreso');
                                    $totalEgresos = (new GetCashBoxOpenedService())->minimalCashBoxTotal('Egreso');
                                    $totalSale = (new GetCashBoxOpenedService())->getTotal(false);
                                    $totalOrder = (new GetCashBoxOpenedService())->getTotal(true, true);
                                    $montoApertura = $get('open_amount') ?? 0;
                                    $totalInCash = ($montoApertura + $totalInresos + $totalOrder + $totalSale) - $totalEgresos;
                                    return new HtmlString(
                                        '<span style="font-weight: 600; color: #FFFFFF; font-size: 16px; background-color: #0056b3; padding: 4px 8px; border-radius: 5px; display: inline-block;">'
                                        . ($totalInCash ?? '-') .
                                        '</span>');
                                })
                                ->hidden(function (?CashBoxOpen $record = null) {
                                    if ($record === null) {
                                        return true;
                                    }
                                }),
                            Forms\Components\Select::make('close_employee_id')
                                ->relationship('closeEmployee', 'name', function ($query) {
                                    $whereHouse = auth()->user()->employee->branch_id;
                                    $query->where('branch_id', $whereHouse);
                                })
                                ->required()
                                ->label('Empleado Cierra')
                                ->hidden(function (?CashBoxOpen $record = null) {
                                    if ($record === null) {
                                        return true;
                                    }
                                })
                                ->options(function () {
                                    $whereHouse = auth()->user()->employee->branch_id;
                                    return Employee::where('branch_id', $whereHouse)
                                        ->pluck('name', 'id');
                                }),
                        ])->columns(3)


                    ])->columns(2)
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cashbox.description')
                    ->placeholder('Caja')
                    ->sortable(),
                Tables\Columns\TextColumn::make('openEmployee.name')
                    ->label('Aperturó')
                    ->sortable(),
                Tables\Columns\TextColumn::make('opened_at')
                    ->label('Fecha de apertura')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('open_amount')
                    ->label('Monto Apertura')
                    ->money('USD', true, locale: 'es_US')
                    ->sortable(),
                Tables\Columns\TextColumn::make('closed_at')
                    ->label('Fecha de cierre')
                    ->placeholder('Sin cerrar')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('closed_amount')
                    ->label('Monto Cierre')
                    ->money('USD', true, locale: 'es_US')
                    ->placeholder('Sin cerrar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('closeEmployee.name')
                    ->label('Cerró')
                    ->placeholder('Sin cerrar')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(function ($query) {
                $query->orderby('created_at', 'desc');
            })
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'open' => 'Abierta',
                        'closed' => 'Cerrada',
                    ])
                    ->label('Estado'),
                Tables\Filters\SelectFilter::make('cash_box_id')
                    ->options(function () {
                        $whereHouse = auth()->user()->employee->branch_id;
                        return \App\Models\CashBox::where('branch_id', $whereHouse)
                            ->get()
                            ->pluck('description', 'id');
                    })
                    ->label('Caja'),
            ])
            ->recordUrl(null)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Cerrar Caja')
                    ->icon('heroicon-o-shield-check')
                    ->hidden(function (CashboxOpen $record) {
                        return $record->status == 'closed';
                    })
                    ->color('danger'),
                Tables\Actions\Action::make('print')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->visible(function (CashboxOpen $record) {
                        return $record->status == 'closed';
                    })
                    ->url(fn($record) => route('closeClashBoxPrint', ['idCasboxClose' => $record->id]))
                    ->openUrlInNewTab() // Esto asegura que se abra en una nueva pestaña

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }



    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashboxOpens::route('/'),
            'create' => Pages\CreateCashboxOpen::route('/create'),
            'edit' => Pages\EditCashboxOpen::route('/{record}/edit'),
        ];
    }


}
