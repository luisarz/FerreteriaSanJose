<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DistritoResource\Pages;
use App\Filament\Resources\DistritoResource\RelationManagers;
use App\Models\Distrito;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;

class DistritoResource extends Resource
{
    protected static ?string $model = Distrito::class;

    protected static  ?string $label= 'Cat-013 Municipios';
    protected static ?bool $softDelete = true;
    protected static ?string $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 13;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Municipio')
                    ->compact()
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Municipio')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('departamento_id')
                            ->relationship('departamento', 'name')
                            ->inlineLabel()
                            ->required()
                            ->columnSpanFull()
                            ->preload()
                            ->searchable(),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Municipio')
                    ->searchable(),
                Tables\Columns\TextColumn::make('departamento.name')
                    ->sortable(),
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
                SelectFilter::make('departamento_id')
                    ->relationship('departamento', 'name')
                    ->preload()
                    ->searchable()
                    ->label('Departamento')
                    ->default(''),
            ])
            ->actions([
//                Tables\Actions\ActionGroup::make([
                Tables\Actions\EditAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\ReplicateAction::make()->label('')->iconSize(IconSize::Medium)->color('success'),
                Tables\Actions\DeleteAction::make()->label('')->iconSize(IconSize::Medium),
//                ]),
//                Tables\Actions\ViewAction::make()->label('')->iconSize(IconSize::Medium),
            ],position: Tables\Enums\ActionsPosition::BeforeColumns)
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
            'index' => Pages\ListDistritos::route('/'),
//            'create' => Pages\CreateDistrito::route('/create'),
//            'edit' => Pages\EditDistrito::route('/{record}/edit'),
        ];
    }
}
