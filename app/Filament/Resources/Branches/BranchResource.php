<?php

namespace App\Filament\Resources\Branches;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Branches\Pages\ListBranches;
use App\Filament\Resources\Branches\Pages\CreateBranch;
use App\Filament\Resources\Branches\Pages\EditBranch;
use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\RelationManagers;
use App\Models\Branch;
use App\Models\Distrito;
use Filament\Forms;
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
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 2;


    public static function getActions(): array
    {
        return [];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informacion de la sucursal')
                    ->schema([
                        Select::make('stablisment_type_id')
                            ->relationship('stablishmenttype', 'name')
                            ->label('Tipo de Establecimiento')
                            ->inlineLabel()
                            ->required()
                            ->preload()
                            ->searchable(),
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Select::make('company_id')
                            ->relationship('company', 'name')
                            ->inlineLabel()
                            ->required()
                            ->preload()
                            ->searchable(),
                        TextInput::make('nrc')
                            ->label('NRC')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nit')
                            ->label('NIT')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('establishment_type_code')
                            ->label('Codigo de Establecimiento')
                            ->required()
                            ->minLength(4)
                            ->maxLength(4),
                        TextInput::make('pos_terminal_code')
                            ->label('Codigo de Terminal POS')
                            ->required()
                            ->minLength(4)
                            ->maxLength(4),

                        Select::make('departamento_id')
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

                        Select::make('distrito_id')
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
                        Select::make('economic_activity_id')
                            ->label('Actividad Economica')
                            ->relationship('economicactivity', 'description')

                            ->preload()
                            ->inlineLabel(false)
                            ->searchable()
                            ->columnSpanFull()
                            ->columns(1)
                            ->required(),
                        TextInput::make('address')
                            ->label('Dirección')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->label('Correo')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('web')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('prices_by_products')
                            ->label('Precios por productos')
                            ->required()
                            ->numeric()
                            ->default(2),

                        FileUpload::make('logo')
                            ->disk('public')
                            ->visibility('public')
                            ->directory('wherehouses')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
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
                TextColumn::make('stablishmenttype.name')
                    ->label('Tipo')
                    ->sortable()
                    ->searchable(),


                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('establishment_type_code')
                    ->label('Codigo de Establecimiento')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('pos_terminal_code')
                    ->label('Codigo de Terminal POS')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('company.name')
                    ->toggleable(isToggledHiddenByDefault: true)

                    ->label('Empresa'),
//                Tables\Columns\TextColumn::make('nit')
//                    ->searchable(),
                TextColumn::make('nrc')
                    ->searchable(),
                TextColumn::make('departamento.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('distrito.name')
              ->label('Municipio')
                    ->sortable(),
//                Tables\Columns\TextColumn::make('address')
//                    ->searchable(),
                TextColumn::make('economic_activity_id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->toggleable(isToggledHiddenByDefault: true)

                    ->searchable(),
                TextColumn::make('web')
                    ->toggleable(isToggledHiddenByDefault: true)
                                        ->searchable(),
                TextColumn::make('prices_by_products')
                    ->label('Precios por productos')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
//                Tables\Columns\IconColumn::make('is_active')
//                    ->boolean(),
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
                //
            ])
            ->recordActions([
//                Tables\Actions\ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    ReplicateAction::make(),
//                    Tables\Actions\DeleteAction::make(),
//                    Tables\Actions\RestoreAction::make(),

//                    ]),
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
            'index' => ListBranches::route('/'),
            'create' => CreateBranch::route('/create'),
            'edit' => EditBranch::route('/{record}/edit'),
        ];
    }
}
