<?php

namespace App\Filament\Resources\Providers;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Providers\Pages\ListProviders;
use App\Filament\Resources\Providers\Pages\CreateProvider;
use App\Filament\Resources\Providers\Pages\EditProvider;
use Exception;
use App\Filament\Resources\ProviderResource\Pages;
use App\Filament\Resources\ProviderResource\RelationManagers;
use App\Models\Distrito;
use App\Models\Municipality;
use App\Models\Provider;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;
    protected static ?string $label = 'Proveedores';
    protected static string | \UnitEnum | null $navigationGroup = 'Inventario';


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Proveedor')
                    ->columns()
                    ->compact()
                    ->schema([
                        TextInput::make('legal_name')
                            ->label('Nombre Legal')
                            ->inlineLabel(false)
                            ->required()
                            ->maxLength(255),
                        TextInput::make('comercial_name')
                            ->label('Nombre Comercial')
                            ->inlineLabel(false)
                            ->required()
                            ->maxLength(255),


                    ]),


                Section::make('Información de Comercial')
                    ->columns(3)
                    ->compact()
//                    ->collapsible()
                    ->schema([
                        Select::make('economic_activity_id')
                            ->relationship('economicactivity', 'description')
                            ->label('Actividad Económica')
                            ->inlineLabel(false)
                            ->preload()
                            ->columnSpanFull()
                            ->searchable()
                            ->required(),
                        Select::make('provider_type')
                            ->label('Tipo de Proveedor')
                            ->options([
                                'Pequeño' => 'Pequeño',
                                'Grande' => 'Grande',
                                'Mediano' => 'Mediano',
                                'Micro' => 'Micro',
                            ]),

                        TextInput::make('nrc')
                            ->label('NRC')
                            ->maxLength(255)
                            ->default(null),
                        TextInput::make('nit')
                            ->label('NIT')
                            ->maxLength(255)
                            ->default(null),
                        Select::make('condition_payment')
                            ->label('Condición de Pago')
                            ->options([
                                'Contado' => 'Contado',
                                'Credito' => 'Credito',
                            ]),
                        TextInput::make('credit_days')
                            ->label('Días de Crédito')
                            ->numeric()
                            ->default(null),
                        TextInput::make('credit_limit')
                            ->label('Límite de Crédito')
                            ->numeric()
                            ->default(null),
                        TextInput::make('balance')
                            ->label('Saldo')
                            ->numeric()
                            ->default(null),
                        DatePicker::make('last_purchase')
                            ->inlineLabel()
                            ->label('Última Compra'),
                        TextInput::make('purchase_decimals')
                            ->label('Decimales de Compra')
                            ->required()
                            ->minLength(1)
                            ->maxLength(1)
                            ->numeric()
                            ->default(2),
                    ]),
                Section::make('Dirección Comercial')
                    ->columns()
                    ->extraAttributes([
                        'class' => 'bg-parimary text-white p-2 rounded-md' // Cambiar el color de fondo y texto
                    ])
                    ->icon('heroicon-o-map-pin')
                    ->compact()
                    ->schema([
                        Select::make('country_id')
                            ->relationship('pais', 'name')
                            ->default(1)
                            ->label('País')
                            ->preload()
//                            ->afterStateUpdated(function ($state, $set) {
//                                if ($state) {
//                                    $set('department_id', null);
//                                }
//                            })
                            ->live()
                            ->searchable(),
                        Select::make('department_id')
                            ->relationship('departamento', 'name')
                            ->label('Departamento')
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $set('municipility_id', null);
                                }
                            })
                            ->preload()
                            ->required(),
                        Select::make('municipility_id')
                            ->label('Municipio')
                            ->live()
                            ->options(function (callable $get) {
                                $idDepartement = $get('department_id');
                                if (!$idDepartement) {
                                    return [];
                                }
                                return Distrito::where('departamento_id', $idDepartement)->pluck('name', 'id');
                            })
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $set('distrito_id', null);
                                }
                            })
                            ->required(),
                        Select::make('distrito_id')
                            ->label('Distrito')
                            ->preload()
                            ->searchable()
                            ->options(function (callable $get) {
                                $idMunicipality = $get('municipility_id');
                                if (!$idMunicipality) {
                                    return [];
                                }
                                return Municipality::where('distrito_id', $idMunicipality)->pluck('name', 'id');
                            })
                            ->required(),
                        TextInput::make('direction')
                            ->maxLength(255)
                            ->inlineLabel(false)
                            ->columnSpanFull()
                            ->default(null),
                    ]),
                Section::make('Información de contacto')
                    ->compact()
                    ->columns()
                    ->schema([
                        TextInput::make('phone_one')
                            ->label('Teléfono Empresa')
                            ->tel()
                            ->maxLength(255)
                            ->default(null),
                        TextInput::make('phone_two')
                            ->label('Teléfono Empresa 2')
                            ->tel()
                            ->maxLength(255)
                            ->default(null),
                        TextInput::make('email')
                            ->label('Correo Empresa')
                            ->email()
                            ->maxLength(255)
                            ->default(null),
                        TextInput::make('contact_seller')
                            ->label('Vendedor')

                            ->maxLength(255)
                            ->default(null),
                        TextInput::make('phone_seller')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255)
                            ->default(null),
                        TextInput::make('email_seller')
                            ->label('Correo')
                            ->email()
                            ->maxLength(255)
                            ->default(null),
                    ]),
                Toggle::make('is_active')
                    ->label('Activo')
                    ->required(),

            ]);
    }

    /**
     * @throws Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->emptyStateDescription('No hay proveedores registrados')
            ->columns([
                TextColumn::make('comercial_name')
                    ->searchable(),
                TextColumn::make('economicactivity.description')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Actividad Económica')
                    ->wrap()
                    ->limit(25)
                    ->searchable(),
//                Tables\Columns\TextColumn::make('nacionality')
//                    ->searchable(),
                TextColumn::make('departamento.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('municipio.name')
                    ->label('Municipio')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('distrito.name')
                    ->label('Distrito')
                    ->numeric()
                    ->sortable(),


                TextColumn::make('phone_one')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('email')
                    ->copyable()
                    ->badge()
                    ->copyMessage('Correo copiado')
                    ->searchable(),
//                Tables\Columns\TextColumn::make('nrc')
//                    ->searchable(),
//                Tables\Columns\TextColumn::make('nit')
//                    ->toggleable(isToggledHiddenByDefault: true)
//                    ->searchable(),

                TextColumn::make('condition_payment')
                ->label('Pago')
                    ->searchable(),
                TextColumn::make('credit_days')
                    ->label('Crédito')
                    ->suffix(' días')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('credit_limit')
                    ->label('Límite')
                    ->money('USD',locale: 'en_US')
                    ->sortable(),
                TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('USD',locale: 'en_US')
                    ->sortable(),
                TextColumn::make('provider_type')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),
                TextColumn::make('contact_seller')
                    ->label('Vendedor')
                    ->placeholder('S/N')
                    ->formatStateUsing(fn ($record) => collect([
                        $record->contact_seller,
                        $record->phone_seller ? "Telf: $record->phone_seller" : null,
                        $record->email_seller ? "Email: $record->email_seller" : null
                    ])->filter()->implode('<br>'))

                    ->html()
                    ->searchable(),
                TextColumn::make('phone_seller')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('S/N')
                    ->searchable(),
                TextColumn::make('email_seller')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('last_purchase')
                    ->toggleable(isToggledHiddenByDefault: true)
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
                SelectFilter::make('department_id')
                    ->label('Departamento')
                    ->relationship('departamento', 'name')
                    ->preload()
                    ->searchable(),
                SelectFilter::make('municipility_id')
                    ->label('Municipio')
                    ->relationship('municipio', 'name')
                    ->preload()
                    ->searchable(),
                TrashedFilter::make('deleted_at')
                    ->label('Mostrar eliminados'),

            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    ReplicateAction::make(),
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
            'index' => ListProviders::route('/'),
            'create' => CreateProvider::route('/create'),
            'edit' => EditProvider::route('/{record}/edit'),
        ];
    }
}
