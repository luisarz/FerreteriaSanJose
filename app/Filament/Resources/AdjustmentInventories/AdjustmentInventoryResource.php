<?php

namespace App\Filament\Resources\AdjustmentInventories;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\AdjustmentInventories\RelationManagers\AdjustmentRelationManager;
use App\Filament\Resources\AdjustmentInventories\Pages\ListAdjustmentInventories;
use App\Filament\Resources\AdjustmentInventories\Pages\CreateAdjustmentInventory;
use App\Filament\Resources\AdjustmentInventories\Pages\EditAdjustmentInventory;
use App\Filament\Resources\AdjustmentInventories\Pages\ViewAdjustment;
use App\Filament\Resources\AdjustmentInventoryResource\Pages;
use App\Filament\Resources\AdjustmentInventoryResource\RelationManagers;
use App\Models\adjustmentInventory;
use App\Models\Employee;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AdjustmentInventoryResource extends Resource
{
    protected static ?string $model = adjustmentInventory::class;
    protected static ?string $label = 'Entradas/Salidas';
    protected static string | \UnitEnum | null $navigationGroup = "Inventario";


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Movimientos de inventario')
                    ->schema([
                        Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                "Entrada" => "Entrada",
                                "Salida" => "Salida"
                            ])
                            ->reactive()
                            ->default('Entrada')
                            ->required(),
                        Select::make('branch_id')
                            ->label('Sucursal')
                            ->debounce(500)
                            ->relationship('branch', 'name')
                            ->searchable()

                            ->preload()
                            ->default(fn() => optional(Auth::user()->employee)->branch_id) // Null-safe check
                            ->required(),


                        DatePicker::make('fecha')
                            ->inlineLabel(true)
                            ->default(now())
                            ->required(),
                        TextInput::make('entidad')
                            ->reactive()
                            ->maxLength(255)
                            ->label(fn(callable $get) => $get('tipo') === 'Entrada' ? 'Proveedor' : 'Cliente'
                            )->required(),
                        Select::make('user_id')
                            ->required()
                            ->debounce(500)
                            ->options(function (callable $get) {
                                $wherehouse = $get('branch_id');
                                if ($wherehouse) {
                                    return Employee::where('branch_id', $wherehouse)->pluck('name', 'id');
                                }
                                return []; // Return an empty array if no wherehouse selected
                            })
                            ->searchable()
                            ->default(fn() => optional(Auth::user()->employee)->id)
                            ->required(),
                        TextInput::make('descripcion')
                            ->label('Motivo')
                            ->required()
                            ->maxLength(255),

                    ])
                    ->columns(3)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo')
                    ->label('Operacion')
                    ->badge()
                    ->color(fn($state) => $state === 'Entrada' ? 'success' : 'danger'),
                TextColumn::make('status')
                    ->label('Operacion')
                    ->badge()
                    ->color(fn($state) => $state === 'FINALIZADO' ? 'success' : 'danger'),
                TextColumn::make('branch.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                TextColumn::make('entidad')
                    ->searchable(),
                TextColumn::make('employee.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('descripcion')
                    ->searchable(),
                TextColumn::make('monto')
                    ->numeric()
                    ->money(currency: 'USD', locale: 'en_US') // Moneda USA

                    ->summarize(Sum::make()->money(currency: 'USD', locale: 'en_US')
                    )
                    ->sortable(),
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
            ->recordUrl(function ($record) {
                return self::getUrl('adjus',
                    [
                        'record' => $record->id
                    ]);
            })
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make('modificar')
                    ->label('')
                    ->color('danger')
                    ->iconSize(IconSize::Large)
                    ->visible(fn($record) => $record->status === 'EN PROCESO'), // Esto asegura que solo se muestre si el registro tiene un DTE
                Action::make('pdf')
                    ->label('') // Etiqueta vacía, si deseas cambiarla, agrega un texto
                    ->icon('heroicon-o-printer')
                    ->tooltip('Imprimir Ticket')
                    ->iconSize(IconSize::Large)
                    ->color('info')
                    ->url(function ($record) {
                        return route('salidaPrintTicket', ['id' => isset($record) ? ($record->id ?? 'SN') : 'SN']);
                    })
                    ->openUrlInNewTab(),


            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AdjustmentRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdjustmentInventories::route('/'),
            'create' => CreateAdjustmentInventory::route('/create'),
            'edit' => EditAdjustmentInventory::route('/{record}/edit'),
            'adjus' => ViewAdjustment::route('/{record}/sale'),

        ];
    }
}
