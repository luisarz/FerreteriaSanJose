<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdjustmentInventoryResource\Pages;
use App\Filament\Resources\AdjustmentInventoryResource\RelationManagers;
use App\Models\adjustmentInventory;
use App\Models\Employee;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class AdjustmentInventoryResource extends Resource
{
    protected static ?string $model = adjustmentInventory::class;
    protected static ?string $label = 'Entradas/Salidas';
    protected static ?string $navigationGroup = "Inventario";


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Movimientos de inventario')
                    ->schema([
                        Forms\Components\Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                "Entrada" => "Entrada",
                                "Salida" => "Salida"
                            ])
                            ->reactive()
                            ->default('Entrada')
                            ->required(),
                        Forms\Components\Select::make('branch_id')
                            ->label('Sucursal')
                            ->debounce(500)
                            ->relationship('branch', 'name')
                            ->searchable()

                            ->preload()
                            ->default(fn() => optional(Auth::user()->employee)->branch_id) // Null-safe check
                            ->required(),


                        Forms\Components\DatePicker::make('fecha')
                            ->inlineLabel(true)
                            ->default(now())
                            ->required(),
                        Forms\Components\TextInput::make('entidad')
                            ->reactive()
                            ->maxLength(255)
                            ->label(fn(callable $get) => $get('tipo') === 'Entrada' ? 'Proveedor' : 'Cliente'
                            )->required(),
                        Forms\Components\Select::make('user_id')
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
                        Forms\Components\TextInput::make('descripcion')
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
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Operacion')
                    ->badge()
                    ->color(fn($state) => $state === 'Entrada' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Operacion')
                    ->badge()
                    ->color(fn($state) => $state === 'FINALIZADO' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('branch.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entidad')
                    ->searchable(),
                Tables\Columns\TextColumn::make('employee.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('monto')
                    ->numeric()
                    ->money(currency: 'USD', locale: 'en_US') // Moneda USA

                    ->summarize(Tables\Columns\Summarizers\Sum::make()->money(currency: 'USD', locale: 'en_US')
                    )
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
            ->actions([
                Tables\Actions\EditAction::make('modificar')
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AdjustmentRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdjustmentInventories::route('/'),
            'create' => Pages\CreateAdjustmentInventory::route('/create'),
            'edit' => Pages\EditAdjustmentInventory::route('/{record}/edit'),
            'adjus' => Pages\ViewAdjustment::route('/{record}/sale'),

        ];
    }
}
