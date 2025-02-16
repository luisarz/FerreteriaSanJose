<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\RelationManagers;
use App\Models\Branch;
use App\Models\Distrito;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;
    protected static ?string $label = 'Sucursales';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 2;


    public static function getActions(): array
    {
        return [];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informacion de la sucursal')
                    ->schema([
                        Forms\Components\Select::make('stablisment_type_id')
                            ->relationship('stablishmenttype', 'name')
                            ->label('Tipo de Establecimiento')
                            ->inlineLabel()
                            ->required()
                            ->preload()
                            ->searchable(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('company_id')
                            ->relationship('company', 'name')
                            ->inlineLabel()
                            ->required()
                            ->preload()
                            ->searchable(),
                        Forms\Components\TextInput::make('nrc')
                            ->label('NRC')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nit')
                            ->label('NIT')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('departamento_id')
                            ->relationship('departamento', 'name')
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $set('distrito_id', null);
                                }
                            })
                            ->preload()
                            ->inlineLabel()
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('distrito_id')
                            ->relationship('distrito', 'name')
                            ->label('Municipio')
                            ->required()
                            ->inlineLabel()
                            ->options(function (callable $get) {
                                $departamentoID = $get('departamento_id');
                                if (!$departamentoID) {
                                    return [];
                                }
                                return Distrito::where('departamento_id', $departamentoID)->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('economic_activity_id')
                            ->label('Actividad Economica')
                            ->relationship('economicactivity', 'description')

                            ->preload()
                            ->inlineLabel(false)
                            ->searchable()
                            ->columnSpanFull()
                            ->columns(1)
                            ->required(),
                        Forms\Components\TextInput::make('address')
                            ->label('Dirección')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->label('Correo')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('web')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('prices_by_products')
                            ->label('Precios por productos')
                            ->required()
                            ->numeric()
                            ->default(2),

                        Forms\Components\FileUpload::make('logo')
                            ->directory('wherehouses')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->inlineLabel()
                            ->label('Activo')
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stablishmenttype.name')
                    ->label('Tipo')
                    ->sortable()
                    ->searchable(),


                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('company.name')
                    ->toggleable(isToggledHiddenByDefault: true)

                    ->label('Empresa'),
//                Tables\Columns\TextColumn::make('nit')
//                    ->searchable(),
                Tables\Columns\TextColumn::make('nrc')
                    ->searchable(),
                Tables\Columns\TextColumn::make('departamento.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('distrito.name')
              ->label('Municipio')
                    ->sortable(),
//                Tables\Columns\TextColumn::make('address')
//                    ->searchable(),
                Tables\Columns\TextColumn::make('economic_activity_id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->toggleable(isToggledHiddenByDefault: true)

                    ->searchable(),
                Tables\Columns\TextColumn::make('web')
                    ->toggleable(isToggledHiddenByDefault: true)
                                        ->searchable(),
                Tables\Columns\TextColumn::make('prices_by_products')
                    ->label('Precios por productos')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
//                Tables\Columns\IconColumn::make('is_active')
//                    ->boolean(),
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
//                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ReplicateAction::make(),
//                    Tables\Actions\DeleteAction::make(),
//                    Tables\Actions\RestoreAction::make(),

//                    ]),
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
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
