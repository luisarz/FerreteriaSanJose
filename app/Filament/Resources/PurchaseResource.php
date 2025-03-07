<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseResource\Pages;
use App\Filament\Resources\PurchaseResource\RelationManagers;
use App\Models\Employee;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

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
                            ->description('Informacion general de la compra')
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
//                                    ->relationship('employee', 'name')
                                    ->label('Empleado')
                                    ->preload()
                                    ->searchable()
                                    ->options(function (callable $get) {
                                        $wherehouse = $get('wherehouse_id');
                                        $saler = \Auth::user()->employee->id ?? null;
                                        if ($wherehouse) {
                                            return Employee::where('id', $saler)->pluck('name', 'id');
                                        }
                                        return []; // Return an empty array if no wherehouse selected
                                    })
                                    ->default(fn() => optional(Auth::user()->employee)->id)
                                    ->required(),
                                Forms\Components\Select::make('wherehouse_id')
                                    ->label('Sucursal')
                                    ->relationship('wherehouse', 'name')
                                    ->default(fn() => \Auth::user()->employee->branch_id)
                                    ->preload()
                                    ->required(),
                                Forms\Components\DatePicker::make('purchase_date')
                                    ->label('Fecha Compra')
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
                                    ->visible(fn(callable $get) => $get('purchase_condition') === 'Crédito') // Solo visible cuando se selecciona "Crédito"
                                    ->required(fn(callable $get) => $get('purchase_condition') === 'Crédito'), // Obligatorio solo si "Crédito" es seleccionado

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
                                    ->required(),
                                Forms\Components\Placeholder::make('net_value')
                                    ->content(function(?Purchase $record) {
                                        return $record ? ($record->net_value ?? 0) : 0;
                                    })
                                    ->inlineLabel()
                                    ->label('Neto'),

                                Forms\Components\Placeholder::make('taxe_value')
                                    ->content(function(?Purchase $record) {
                                        return $record ? ($record->taxe_value ?? 0) : 0;
                                    })
                                    ->inlineLabel()
                                    ->label('IVA'),

                                Forms\Components\Placeholder::make('perception_value')
                                    ->content(fn(?Purchase $record) => $record->perception_value??0)
                                    ->inlineLabel()
                                    ->label('Percepción:'),

                                Forms\Components\Placeholder::make('purchase_total')
                                    ->label('Total')
                                    ->content(fn(?Purchase $record) => new HtmlString('<span style="font-weight: bold; color: red; font-size: 18px;">$ ' . number_format($record->purchase_total??0, 2) . '</span>'))
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
                Tables\Columns\TextColumn::make('status'),
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
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
//                Tables\Actions\EditAction::make()->label('Modificar'),
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
        ];
    }


}
