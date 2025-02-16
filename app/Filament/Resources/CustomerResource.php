<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Models\Distrito;
use App\Models\Municipality;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationGroup = "Facturación";
    protected static ?string $label = 'Clientes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make('')
                    ->description('Información personal del cliente')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Select::make('person_type_id')
                            ->relationship('persontype', 'name')
                            ->label('Tipo de persona')
                            ->required()
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('document_type_id')
                            ->relationship('documenttypecustomer', 'name')
                            ->label('Tipo de documento')
                            ->required()
                            ->preload()
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->label('Nombre')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->label('Apellido')
                            ->maxLength(255),

                    ])->compact()
                    ->columns(2),
                Forms\Components\Section::make('Información Comercial')
                    ->compact()
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('economic_activities_id')
                            ->relationship('economicactivity', 'description')
                            ->label('Actividad Económica')
                            ->preload()
                            ->searchable(),
                        Forms\Components\Select::make('wherehouse_id')
                            ->relationship('wherehouse', 'name')
                            ->default(fn() => Auth()->user()->employee->wherehouse->id)
                            ->label('Sucursal')
                            ->preload()
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('nrc')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('dui')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('nit')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\Toggle::make('is_taxed')
                            ->label('Paga IVA')
                            ->default(true),
                    ])
                ,
                Forms\Components\Section::make('Información de contacto')
                    ->compact()
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->mask('(999) 9999-9999')
                            ->default(503)
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\Select::make('country_id')
                            ->label('País')
                            ->relationship('country', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('departamento_id')
                            ->label('Departamento')
                            ->relationship('departamento', 'name')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if (!$state) {
                                    $set('distrito_id', null);
                                    $set('municipio_id', null);
                                }
                            })
                            ->preload(),
                        Forms\Components\Select::make('distrito_id')
                            ->label('Municipio')
                            ->live()
                            ->preload()
                            ->searchable()
                            ->afterStateUpdated(function ($state, $set) {
                                if (!$state) {
                                    $set('municipio_id', null);
                                }
                            })
                            ->options(function (callable $get) {
                                $departamentoID = $get('departamento_id');
                                if (!$departamentoID) {
                                    return [];
                                }
                                return Distrito::where('departamento_id', $departamentoID)->pluck('name', 'id');
                            }),
                        Forms\Components\Select::make('municipio_id')
                            ->label('Distrito')
                            ->options(function (callable $get) {
                                $distritoID = $get('distrito_id');
                                if (!$distritoID) {
                                    return [];
                                }
                                return Municipality::where('distrito_id', $distritoID)->pluck('name', 'id');
                            }),
                    ]),
                Forms\Components\Section::make('Información general')
                    ->compact()
                    ->columns(2)
                    ->schema([

                        Forms\Components\TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\Toggle::make('is_credit_client')
                            ->label('Cliente de crédito')
                            ->required(),
                        Forms\Components\TextInput::make('credit_limit')
                            ->label('Límite de crédito')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('credit_days')
                            ->label('Días de crédito')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('credit_balance')
                            ->label('Saldo de crédito')
                            ->numeric()
                            ->default(null),
                        Forms\Components\DatePicker::make('last_purched')
                            ->label('Última compra')
                            ->inlineLabel(true)
                            ->default(null),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->required(),

                    ]),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wherehouse.name')
                    ->label('Sucursal')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label('Apellido')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nrc')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('dui')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nit')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->url(fn($record) => 'https://wa.me/' . preg_replace('/[^0-9]/', '', $record->phone), true)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->iconColor('success')
                    ->tooltip('Enviar mensaje a WhatsApp'),


                Tables\Columns\TextColumn::make('country.name')
                    ->label('País')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('departamento.name')
                    ->label('Departamento')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('distrito.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('municipio.name')
                    ->label('Distrito')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_taxed')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Paga IVA')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_active')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_credit_client')
                    ->label('Crédito')
                    ->boolean(),
                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Límite de crédito')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_days')
                    ->label('Días de crédito')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_balance')
                    ->label('Saldo de crédito')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_purched')
                    ->label('Última compra')
                    ->placeholder('Sin compras')
                    ->date()
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
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\SelectFilter::make('wherehouse_id')
                    ->relationship('wherehouse', 'name')
                    ->label('Sucursal')
                    ->searchable()
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\ReplicateAction::make()->label('Duplicar'),
                    Tables\Actions\DeleteAction::make(),
                    Tables\Actions\RestoreAction::make(),

                ]),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
