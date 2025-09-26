<?php

namespace App\Filament\Resources\Customers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use App\Models\Departamento;
use App\Models\Distrito;
use App\Models\Municipality;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string | \UnitEnum | null $navigationGroup = "Facturación";
    protected static ?string $label = 'Clientes';
    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'last_name', 'nrc', 'dui', 'nit', 'email', 'phone'];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                    ->description('Información personal del cliente')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Select::make('person_type_id')
                            ->relationship('persontype', 'name')
                            ->label('Tipo de persona')
                            ->required()
                            ->preload()
                            ->searchable(),
                        Select::make('document_type_id')
                            ->relationship('documenttypecustomer', 'name')
                            ->label('Tipo de documento')
                            ->required()
                            ->preload()
                            ->searchable(),
                        TextInput::make('name')
                            ->required()
                            ->label('Nombre')
                            ->maxLength(255),
                        TextInput::make('last_name')
                            ->label('Apellido')
                            ->maxLength(255),

                    ])->compact()
                    ->columns(2),
                Section::make('Información Comercial')
                    ->compact()
                    ->columns(2)
                    ->schema([
                        Select::make('economic_activities_id')
                            ->relationship('economicactivity', 'description')
                            ->label('Actividad Económica')
                            ->preload()
                            ->required()
                            ->searchable(),
                        Select::make('wherehouse_id')
                            ->relationship('wherehouse', 'name')
                            ->default(fn() => Auth()->user()->employee->wherehouse->id)
                            ->label('Sucursal')
                            ->preload()
                            ->searchable()
                            ->required(),
                        TextInput::make('nrc')
                            ->maxLength(255)
                            ->default(null),
                        TextInput::make('dui')
                            ->maxLength(255)
                            ->default(null),
                        TextInput::make('nit')
                            ->maxLength(255)
                            ->default(null),
                        Toggle::make('is_taxed')
                            ->label('Paga IVA')
                            ->default(true),
                    ])
                ,
                Section::make('Información de contacto')
                    ->compact()
                    ->columns(2)
                    ->schema([
                        TextInput::make('email')
                            ->label('Correo')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->mask('(999) 9999-9999')
                            ->required()
                            ->default(503)
                            ->tel()
                            ->maxLength(255),
                        Select::make('country_id')
                            ->label('País')
                            ->required()
                            ->relationship('country', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('departamento_id')
                            ->label('Departamento')
                            ->required()
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
                        Select::make('distrito_id')
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
                            })
                            ->createOptionForm([
                                Section::make('Información del Municipio')
                                    ->compact()
                                    ->schema([
                                        TextInput::make('code')
                                            ->label('Código')
                                            ->required()
                                            ->maxLength(255),

                                        TextInput::make('name')
                                            ->label('Municipio')
                                            ->required()
                                            ->maxLength(255),

                                        Select::make('departamento_id')
                                            ->label('Departamento')
                                            ->required()
                                            ->columnSpanFull()
                                            ->searchable()
                                            ->getSearchResultsUsing(fn(string $search) => Departamento::where('name', 'like', "%{$search}%")
                                                ->limit(50)
                                                ->pluck('name', 'id')
                                            )
                                            ->getOptionLabelUsing(fn($value): ?string => Departamento::find($value)?->name
                                            )
                                            ->preload()
                                            ->live(),
                                    ]),
                            ])->createOptionUsing(function ($data) {
                                return Distrito::create($data)->id; // Guarda y devuelve el ID del nuevo cliente
                            }),
                        Select::make('municipio_id')
                            ->label('Distrito')
                            ->options(function (callable $get) {
                                $distritoID = $get('distrito_id');
                                if (!$distritoID) {
                                    return [];
                                }
                                return Municipality::where('distrito_id', $distritoID)->pluck('name', 'id');
                            })
                            ->createOptionForm([
                                    Section::make('Información de Distrito')
                                        ->schema([
                                            TextInput::make('code')
                                                ->label('Código')  // Etiqueta opcional
                                                ->required()
                                                ->maxLength(255),
                                            TextInput::make('name')
                                                ->label('Distrito')  // Etiqueta opcional
                                                ->required()
                                                ->maxLength(255),
                                            Select::make('distrito_id')
                                                ->label('Municipio')
                                                ->required()
//                                                ->relationship('distrito', 'name')  // Relación con el modelo 'distrito'
//                                                ->columnSpanFull()
                                                ->searchable()
                                                ->getSearchResultsUsing(fn(string $search) => Distrito::where('name', 'like', "%{$search}%")
                                                    ->limit(50)
                                                    ->pluck('name', 'id')
                                                )
                                                ->getOptionLabelUsing(fn($value): ?string => Distrito::find($value)?->name
                                                )
                                                ->preload()
                                                ->live(),
                                            Toggle::make('is_active')
                                                ->label('¿Está Activo?')  // Etiqueta opcional para mayor claridad
                                                ->required(),
                                        ])
                                        ->columns(2),  // Define que los campos se dividan en 2 columnas
                                ]
                            )->createOptionUsing(function ($data) {
                                return Municipality::create($data)->id; // Guarda y devuelve el ID del nuevo cliente
                            }),
                    ]),
                Section::make('Información general')
                    ->compact()
                    ->columns(2)
                    ->schema([

                        TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->required()
                            ->default(null),
                        Toggle::make('is_credit_client')
                            ->label('Cliente de crédito')
                            ->required(),
                        TextInput::make('credit_limit')
                            ->label('Límite de crédito')
                            ->numeric()
                            ->default(null),
                        TextInput::make('credit_days')
                            ->label('Días de crédito')
                            ->numeric()
                            ->default(null),
                        TextInput::make('credit_balance')
                            ->label('Saldo de crédito')
                            ->numeric()
                            ->default(null),
                        DatePicker::make('last_purched')
                            ->label('Última compra')
                            ->inlineLabel(true)
                            ->default(null),
                        Toggle::make('is_active')
                            ->default(true)
                            ->required(),

                    ]),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('wherehouse.name')
                    ->label('Sucursal')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('last_name')
                    ->label('Apellido')
                    ->searchable(),
                TextColumn::make('nrc')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('dui')
                    ->searchable(),
                TextColumn::make('nit')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->url(fn($record) => 'https://wa.me/' . preg_replace('/[^0-9]/', '', $record->phone), true)
                    ->openUrlInNewTab()
                    ->icon('heroicon-o-chat-bubble-oval-left-ellipsis')
                    ->iconColor('success')
                    ->tooltip('Enviar mensaje a WhatsApp'),


                TextColumn::make('country.name')
                    ->label('País')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('departamento.name')
                    ->label('Departamento')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('distrito.name')
                    ->label('Municipio')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('municipio.name')
                    ->label('Distrito')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_taxed')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Paga IVA')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),
                IconColumn::make('is_credit_client')
                    ->label('Crédito')
                    ->boolean(),
                TextColumn::make('credit_limit')
                    ->label('Límite de crédito')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('credit_days')
                    ->label('Días de crédito')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('credit_balance')
                    ->label('Saldo de crédito')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_purched')
                    ->label('Última compra')
                    ->placeholder('Sin compras')
                    ->date()
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
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('wherehouse_id')
                    ->relationship('wherehouse', 'name')
                    ->label('Sucursal')
                    ->searchable()
                    ->multiple(),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
                    ReplicateAction::make()->label('Duplicar'),
                    DeleteAction::make(),
                    RestoreAction::make(),

                ]),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
