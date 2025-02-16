<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EconomicActivityResource\Pages;
use App\Filament\Resources\EconomicActivityResource\RelationManagers;
use App\Models\EconomicActivity;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EconomicActivityResource extends Resource
{
    protected static ?string $model = EconomicActivity::class;
    protected static ?string $label = 'Cat-019 Actividades Económicas';
    protected static string $icon = 'heroicon-o-collection';
    protected static $softDelete = true;
    protected static ?string $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 19;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Código')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('description')
                        ->label('Actividad económica')
                        ->required()
                        ->maxLength(255),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
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
                Tables\Actions\EditAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\DeleteAction::make()->label('')->iconSize(IconSize::Medium),
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
            'index' => Pages\ListEconomicActivities::route('/'),
//            'create' => Pages\CreateEconomicActivity::route('/create'),
//            'edit' => Pages\EditEconomicActivity::route('/{record}/edit'),
        ];
    }
}
