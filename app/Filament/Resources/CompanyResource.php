<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers;
use App\Models\Company;
use App\Models\Distrito;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;
    protected static ?string $label = 'Conf. Globales';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 1;
    public static function getActions(): array
    {
        return [];
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->compact()
                    ->schema([

                        Forms\Components\TextInput::make('name')
                            ->label('Empresa')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('nrc')
                            ->label('No Regisro')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('nit')
                            ->label('NIT')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->label('Teléfono')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('whatsapp')
                            ->required()
                            ->label('WhatsApp')
                            ->inlineLabel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->label('Correo')
                            ->inlineLabel()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('economic_activity_id')
                            ->relationship('economicactivity', 'description')
                            ->required()
                            ->preload()
                            ->searchable()
                            ->label('Rubro')
                            ->inlineLabel(),
                        Forms\Components\Select::make('country_id')
                            ->required()
                            ->inlineLabel()
                            ->relationship('country', 'name')
                            ->preload()
                            ->searchable(),
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
                        Forms\Components\TextInput::make('address')
                            ->required()
                            ->inlineLabel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('web')
                            ->required()
                            ->inlineLabel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('api_key')
                            ->maxLength(255)
                            ->password()
                            ->revealable(true)
                            ->inlineLabel()
                            ->default(null),
                        Forms\Components\FileUpload::make('logo')
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
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->circular(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nrc')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nit')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('economicactivity.description')
                    ->color('primary')
                    ->icon('heroicon-o-shield-check')
                    ->iconPosition('before')
                    ->label('Actividad Comercial')
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('web')
                    ->searchable(),
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListCompanies::route('/'),
//            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
