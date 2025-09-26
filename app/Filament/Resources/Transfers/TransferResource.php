<?php

namespace App\Filament\Resources\Transfers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use App\Models\Branch;
use Filament\Resources\Pages\EditRecord;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Transfers\RelationManagers\TransferItemsRelationManager;
use App\Filament\Resources\Transfers\Pages\ListTransfers;
use App\Filament\Resources\Transfers\Pages\CreateTransfer;
use App\Filament\Resources\Transfers\Pages\EditTransfer;
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
use Filament\Forms\Components\Select;
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

    protected static string | \UnitEnum | null $navigationGroup = "Inventario";
    protected static ?string $label = 'Traslados';
    protected static bool $softDelete = true;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                    ->schema([

                        Grid::make(12)
                            ->schema([

                                Section::make('Origien')
                                    ->icon('heroicon-o-user')
                                    ->iconColor('success')
                                    ->compact()
                                    ->schema([
                                        Select::make('wherehouse_from')
                                            ->label('Sucursal Origen')
                                            ->relationship('wherehouseFrom', 'name', function ($query) {
                                                $actualbranch = auth()->user()->employee->branch_id;
                                                $query->where('id', $actualbranch); // Filtrar por la sucursal actual
                                            })
                                            ->default(function () {
                                                return Branch::where('id', auth()->user()->employee->branch_id)->first()?->id;
                                            })
                                            ->disabled(function ($livewire) {
                                                return $livewire instanceof EditRecord; // Deshablitar en modo edicion
                                            })
                                            ->required(),
                                        Select::make('user_send')
                                            ->label('Empleado Envia')
                                            ->required()
                                            ->preload()
                                            ->relationship('userSend', 'name')
                                            ->searchable(),

                                        DateTimePicker::make('transfer_date')
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
                                        Placeholder::make('transfer_number')
                                            ->label('Traslado')
                                            ->content(fn(?Transfer $record) => new HtmlString(
                                                '<span style="font-weight: 600; color: #FFFFFF; font-size: 14px; background-color: #0056b3; padding: 4px 8px; border-radius: 5px; display: inline-block;">'
                                                . ($record->transfer_number ?? '-') .
                                                '</span>'
                                            ))
                                            ->inlineLabel()
                                            ->extraAttributes(['class' => 'p-0 text-lg']) // Tailwind classes for padding and font size
                                            ->columnSpan('full'),
                                        Select::make('wherehouse_to')
                                            ->label('Sucursal')
                                            ->relationship('wherehouseTo', 'name', function ($query) {
                                                $actualbranch = auth()->user()->employee->branch_id;
                                                $query->where('id', '!=', $actualbranch); // Filtrar por la sucursal actual
                                            })
                                            ->disabled(function ($livewire) {
                                                return $livewire instanceof EditRecord; // Deshablitar en modo edicion
                                            })
                                            ->required(),

                                        Placeholder::make('total')
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
                TextColumn::make('transfer_number')
                    ->label('Traslado')
                    ->searchable(),
                TextColumn::make('wherehouseFrom.name')
                    ->label('Origen')
                    ->sortable(),
                TextColumn::make('userSend.name')
                    ->label('Envió')
                    ->sortable(),
                TextColumn::make('wherehouseTo.name')
                    ->label('Destino')
                    ->sortable(),
                TextColumn::make('userRecive.name')
                    ->label('Recibió')
                    ->sortable(),
                TextColumn::make('transfer_date')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('received_date')
                    ->label('Fecha Recibido')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('total')
                    ->money('USD', locale: 'es_US')
                    ->sortable(),
                TextColumn::make('status_send')
                    ->label('Estado Envio')
                    ->searchable(),
                TextColumn::make('status_received')
                    ->label('Estado Recibido')
                    ->searchable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordUrl(null)
            ->filters([
                //
            ])
            ->recordActions([
                transferActions::printTransfer(),
                transferActions::anularTransfer(),
//                transferActions::recibirTransferParcial(),
                transferActions::recibirTransferFull(),

            ], RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TransferItemsRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTransfers::route('/'),
            'create' => CreateTransfer::route('/create'),
            'edit' => EditTransfer::route('/{record}/edit'),
        ];
    }
}
