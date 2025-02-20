<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProviderResource\Pages;
use App\Filament\Resources\ProviderResource\RelationManagers;
use App\Models\Distrito;
use App\Models\Municipality;
use App\Models\Provider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProviderResource extends Resource
{
    protected static ?string $model = Provider::class;
    protected static ?string $label = 'Proveedores';
    protected static ?string $navigationGroup = 'Inventario';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Proveedor')
                    ->columns()
                    ->compact()
                    ->schema([
                        Forms\Components\TextInput::make('legal_name')
                            ->label('Nombre Legal')
                            ->inlineLabel(false)
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('comercial_name')
                            ->label('Nombre Comercial')
                            ->inlineLabel(false)
                            ->required()
                            ->maxLength(255),


                    ]),


                Forms\Components\Section::make('Información de Comercial')
                    ->columns(3)
                    ->compact()
//                    ->collapsible()
                    ->schema([
                        Forms\Components\Select::make('economic_activity_id')
                            ->relationship('economicactivity', 'description')
                            ->label('Actividad Económica')
                            ->inlineLabel(false)
                            ->preload()
                            ->columnSpanFull()
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('provider_type')
                            ->label('Tipo de Proveedor')
                            ->options([
                                'Pequeño' => 'Pequeño',
                                'Grande' => 'Grande',
                                'Mediano' => 'Mediano',
                                'Micro' => 'Micro',
                            ]),

                        Forms\Components\TextInput::make('nrc')
                            ->label('NRC')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('nit')
                            ->label('NIT')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\Select::make('condition_payment')
                            ->label('Condición de Pago')
                            ->options([
                                'Contado' => 'Contado',
                                'Credito' => 'Credito',
                            ]),
                        Forms\Components\TextInput::make('credit_days')
                            ->label('Días de Crédito')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('credit_limit')
                            ->label('Límite de Crédito')
                            ->numeric()
                            ->default(null),
                        Forms\Components\TextInput::make('balance')
                            ->label('Saldo')
                            ->numeric()
                            ->default(null),
                        Forms\Components\DatePicker::make('last_purchase')
                            ->inlineLabel()
                            ->label('Última Compra'),
                        Forms\Components\TextInput::make('purchase_decimals')
                            ->label('Decimales de Compra')
                            ->required()
                            ->minLength(1)
                            ->maxLength(1)
                            ->numeric()
                            ->default(2),
                    ]),
                Forms\Components\Section::make('Dirección Comercial')
                    ->columns()
                    ->extraAttributes([
                        'class' => 'bg-parimary text-white p-2 rounded-md' // Cambiar el color de fondo y texto
                    ])
                    ->icon('heroicon-o-map-pin')
                    ->compact()
                    ->schema([
                        Forms\Components\Select::make('country_id')
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
                        Forms\Components\Select::make('department_id')
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
                        Forms\Components\Select::make('municipility_id')
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
                        Forms\Components\Select::make('distrito_id')
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
                        Forms\Components\TextInput::make('direction')
                            ->maxLength(255)
                            ->inlineLabel(false)
                            ->columnSpanFull()
                            ->default(null),
                    ]),
                Forms\Components\Section::make('Información de contacto')
                    ->compact()
                    ->columns()
                    ->schema([
                        Forms\Components\TextInput::make('phone_one')
                            ->label('Teléfono Empresa')
                            ->tel()
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('phone_two')
                            ->label('Teléfono Empresa 2')
                            ->tel()
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Empresa')
                            ->email()
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('contact_seller')
                            ->label('Vendedor')

                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('phone_seller')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('email_seller')
                            ->label('Correo')
                            ->email()
                            ->maxLength(255)
                            ->default(null),
                    ]),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->required(),

            ]);
    }

    /**
     * @throws \Exception
     */
    public static function table(Table $table): Table
    {
        return $table
            ->deferLoading()
            ->emptyStateDescription('No hay proveedores registrados')
            ->columns([
                Tables\Columns\TextColumn::make('comercial_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('economicactivity.description')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Actividad Económica')
                    ->wrap()
                    ->limit(25)
                    ->searchable(),
//                Tables\Columns\TextColumn::make('nacionality')
//                    ->searchable(),
                Tables\Columns\TextColumn::make('departamento.name')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('municipio.name')
                    ->label('Municipio')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('distrito.name')
                    ->label('Distrito')
                    ->numeric()
                    ->sortable(),


                Tables\Columns\TextColumn::make('phone_one')
                    ->label('Teléfono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->copyable()
                    ->badge()
                    ->copyMessage('Correo copiado')
                    ->searchable(),
//                Tables\Columns\TextColumn::make('nrc')
//                    ->searchable(),
//                Tables\Columns\TextColumn::make('nit')
//                    ->toggleable(isToggledHiddenByDefault: true)
//                    ->searchable(),

                Tables\Columns\TextColumn::make('condition_payment')
                ->label('Pago')
                    ->searchable(),
                Tables\Columns\TextColumn::make('credit_days')
                    ->label('Crédito')
                    ->suffix(' días')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_limit')
                    ->label('Límite')
                    ->money('USD',locale: 'en_US')
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('USD',locale: 'en_US')
                    ->sortable(),
                Tables\Columns\TextColumn::make('provider_type')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->boolean(),
                Tables\Columns\TextColumn::make('contact_seller')
                    ->label('Vendedor')
                    ->placeholder('S/N')
                    ->formatStateUsing(fn ($record) => collect([
                        $record->contact_seller,
                        $record->phone_seller ? "Telf: $record->phone_seller" : null,
                        $record->email_seller ? "Email: $record->email_seller" : null
                    ])->filter()->implode('<br>'))

                    ->html()
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone_seller')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('S/N')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email_seller')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_purchase')
                    ->toggleable(isToggledHiddenByDefault: true)
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
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Departamento')
                    ->relationship('departamento', 'name')
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('municipility_id')
                    ->label('Municipio')
                    ->relationship('municipio', 'name')
                    ->preload()
                    ->searchable(),
                Tables\Filters\TrashedFilter::make('deleted_at')
                    ->label('Mostrar eliminados'),

            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ReplicateAction::make(),
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
            'index' => Pages\ListProviders::route('/'),
            'create' => Pages\CreateProvider::route('/create'),
            'edit' => Pages\EditProvider::route('/{record}/edit'),
        ];
    }
}
