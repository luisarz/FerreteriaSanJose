<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransferResource\Pages;
use App\Filament\Resources\TransferResource\RelationManagers;
use App\Models\CashBoxCorrelative;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Sale;
use App\Models\Transfer;
use App\Service\GetCashBoxOpenedService;
use App\Tables\Actions\transferActions;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class TransferResource extends Resource
{
    protected static ?string $model = Transfer::class;

    protected static ?string $navigationGroup = "Inventario";
    protected static ?string $label = 'Traslados';
    protected static bool $softDelete = true;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')
                    ->schema([

                        Grid::make(12)
                            ->schema([

                                Section::make('Origien')
                                    ->icon('heroicon-o-user')
                                    ->iconColor('success')
                                    ->compact()
                                    ->schema([
                                        Forms\Components\Select::make('wherehouse_from')
                                            ->label('Sucursal Origen')
                                            ->relationship('wherehouseFrom', 'name', function ($query) {
                                                $actualbranch = auth()->user()->employee->branch_id;
                                                $query->where('id', $actualbranch); // Filtrar por la sucursal actual
                                            })
                                            ->default(function () {
                                                return \App\Models\Branch::where('id', auth()->user()->employee->branch_id)->first()?->id;
                                            })
                                            ->disabled(function ($livewire) {
                                                return $livewire instanceof \Filament\Resources\Pages\EditRecord; // Deshablitar en modo edicion
                                            })
                                            ->required(),
                                        Forms\Components\Select::make('user_send')
                                            ->label('Empleado Envia')
                                            ->required()
                                            ->preload()
                                            ->relationship('userSend', 'name')
                                            ->searchable(),

                                        Forms\Components\DateTimePicker::make('transfer_date')
                                            ->inlineLabel(true)
                                            ->default(now())
                                            ->label('Fecha de Traslado')
                                            ->required(),

//                                        Forms\Components\Select::make('status_send')
//                                            ->label('Estado del Envio')
//                                            ->required()
//                                            ->options([
//                                                'pendiente' => 'Pendiente',
//                                                'enviado' => 'Enviado',
//                                                'recibido' => 'Recibido',
//                                            ])
//                                            ->hidden(function ($livewire) {
//                                                return $livewire instanceof \Filament\Resources\Pages\CreateRecord; // Ocultar en modo creación
//                                            })
//                                            ->default('pendiente'),


                                    ])->columnSpan(9)
                                    ->extraAttributes([
                                        'class' => 'bg-blue-100 border border-blue-500 rounded-md p-2',
                                    ])
                                    ->columns(2),


                                Section::make('Destino')
                                    ->compact()
                                    ->schema([
                                        Forms\Components\Placeholder::make('transfer_number')
                                            ->label('Traslado')
                                            ->content(fn(?Transfer $record) => new HtmlString(
                                                '<span style="font-weight: 600; color: #FFFFFF; font-size: 14px; background-color: #0056b3; padding: 4px 8px; border-radius: 5px; display: inline-block;">'
                                                . ($record->transfer_number ?? '-') .
                                                '</span>'
                                            ))
                                            ->inlineLabel()
                                            ->extraAttributes(['class' => 'p-0 text-lg']) // Tailwind classes for padding and font size
                                            ->columnSpan('full'),
                                        Forms\Components\Select::make('wherehouse_to')
                                            ->label('Sucursal')
                                            ->relationship('wherehouseTo', 'name', function ($query) {
                                                $actualbranch = auth()->user()->employee->branch_id;
                                                $query->where('id', '!=', $actualbranch); // Filtrar por la sucursal actual
                                            })
                                            ->disabled(function ($livewire) {
                                                return $livewire instanceof \Filament\Resources\Pages\EditRecord; // Deshablitar en modo edicion
                                            })
                                            ->required(),

                                        Forms\Components\Placeholder::make('total')
                                            ->label('Total')
                                            ->content(fn(?Transfer $record) => new HtmlString('<span style="font-weight: bold; color: red; font-size: 18px;">$ ' . number_format($record->total ?? 0, 2) . '</span>'))
                                            ->inlineLabel()
                                            ->extraAttributes(['class' => 'p-0 text-lg']) // Tailwind classes for padding and font size
                                            ->columnSpan('full'),
                                    ])
                                    ->extraAttributes([
                                        'class' => 'bg-blue-100 border border-blue-500 rounded-md p-2',
                                    ])
                                    ->columnSpan(3)->columns(1),
                            ]),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transfer_number')
                    ->label('Traslado')
                    ->searchable(),
                Tables\Columns\TextColumn::make('wherehouseFrom.name')
                    ->label('Origen')
                    ->sortable(),
                Tables\Columns\TextColumn::make('userSend.name')
                    ->label('Envió')
                    ->sortable(),
                Tables\Columns\TextColumn::make('wherehouseTo.name')
                    ->label('Destino')
                    ->sortable(),
                Tables\Columns\TextColumn::make('userRecive.name')
                    ->label('Recibió')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transfer_date')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('received_date')
                    ->label('Fecha Recibido')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->money('USD', locale: 'es_US')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_send')
                    ->label('Estado Envio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status_received')
                    ->label('Estado Recibido')
                    ->searchable(),
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
            ->recordUrl(null)
            ->filters([
                //
            ])
            ->actions([
                transferActions::printTransfer(),
                transferActions::anularTransfer(),
//                transferActions::recibirTransferParcial(),
                transferActions::recibirTransferFull(),

            ], Tables\Enums\ActionsPosition::BeforeCells)
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransferItemsRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransfers::route('/'),
            'create' => Pages\CreateTransfer::route('/create'),
            'edit' => Pages\EditTransfer::route('/{record}/edit'),
        ];
    }
}
