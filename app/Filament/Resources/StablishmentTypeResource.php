<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StablishmentTypeResource\Pages;
use App\Filament\Resources\StablishmentTypeResource\RelationManagers;
use App\Models\StablishmentType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StablishmentTypeResource extends Resource
{
    protected static ?string $model = StablishmentType::class;
    protected static ?string $label = 'Cat-009 Tipos de Establecimiento';
    protected static ?string $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([


                Forms\Components\Section::make('Información Tipo de Establecimiento')
                    ->compact()
                    ->columns(1)
                    ->schema([

                        Forms\Components\TextInput::make('code')
                            ->label('Código')
                            ->maxLength(255)
                            ->default(null),
                        Forms\Components\TextInput::make('name')
                            ->label('Tipo de establecimiento')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->required(),
                    ])

            ])->extraAttributes(['class' => 'text-center']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
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
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
//                Tables\Actions\ActionGroup::make( [
                Tables\Actions\ViewAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\EditAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\ReplicateAction::make()->label('')->iconSize(IconSize::Medium)->color('success'),
                Tables\Actions\DeleteAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\RestoreAction::make()->label('')->iconSize(IconSize::Medium),
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
            'index' => Pages\ListStablishmentTypes::route('/'),
//            'create' => Pages\CreateStablishmentType::route('/create'),
//            'edit' => Pages\EditStablishmentType::route('/{record}/edit'),
        ];
    }
}
