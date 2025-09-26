<?php

namespace App\Filament\Resources\Companies;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use App\Models\Distrito;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $label = 'Conf. Globales';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 1;
    public static function getActions(): array
    {
        return [];
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->compact()
                    ->schema([

                        TextInput::make('name')
                            ->label('Empresa')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),

                        TextInput::make('nrc')
                            ->label('No Regisro')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('nit')
                            ->label('NIT')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->label('Teléfono')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        TextInput::make('whatsapp')
                            ->required()
                            ->label('WhatsApp')
                            ->inlineLabel()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->label('Correo')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),

                        Select::make('economic_activity_id')
                            ->relationship('economicactivity', 'description')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->label('Rubro')
                            ->inlineLabel(),
                        Select::make('country_id')
                            ->required()
                            ->inlineLabel()
                            ->relationship('country', 'name')
                            ->preload()
                            ->searchable(),
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
                        TextInput::make('address')
                            ->required()
                            ->inlineLabel()
                            ->maxLength(255),
                        TextInput::make('web')
                            ->required()
                            ->inlineLabel()
                            ->maxLength(255),
                        TextInput::make('api_key')
                            ->maxLength(255)
                            ->password()
                            ->revealable(true)
                            ->inlineLabel()
                            ->default(null),
                        FileUpload::make('logo')
                            ->directory('/configuracion')
                            ->avatar()
                            ->imageEditor()
                            ->inlineLabel()
                        ->columnSpanFull(),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('nrc')
                    ->searchable(),
                TextColumn::make('nit')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('economicactivity.description')
                    ->color('primary')
                    ->icon('heroicon-o-shield-check')
                    ->iconPosition('before')
                    ->label('Actividad Comercial')
                    ->wrap()
                    ->sortable(),
                TextColumn::make('web')
                    ->searchable(),
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => ListCompanies::route('/'),
//            'create' => Pages\CreateCompany::route('/create'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }
}
