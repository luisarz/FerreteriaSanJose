<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OperationConditionResource\Pages;
use App\Filament\Resources\OperationConditionResource\RelationManagers;
use App\Models\OperationCondition;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OperationConditionResource extends Resource
{
    protected static ?string $model = OperationCondition::class;

    protected static ?string $label = 'Cat-016 Condiciones de operacióne';
    protected static ?string $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 16;
    public static function getNavigationLabel(): string
    {
        return substr(static::$label, 0, -1);
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
               Forms\Components\Section::make('Información Condición de operación')
                   ->description('Información de la condición de operación')
                   ->icon('heroicon-o-credit-card')
                   ->iconColor('info')
                   ->columns(2)
                   ->compact()
                 ->schema([
                     Forms\Components\TextInput::make('code')
                         ->label('Código')
                         ->required()
                         ->maxLength(255),
                     Forms\Components\TextInput::make('name')
                         ->label('Condición de operación')
                         ->required()
                         ->maxLength(255),
                     Forms\Components\Toggle::make('is_active')
                         ->label('Activo')
                         ->required(),
                     ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Condición de operación')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
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
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()->label('Ver'),
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
            'index' => Pages\ListOperationConditions::route('/'),
            'create' => Pages\CreateOperationCondition::route('/create'),
            'edit' => Pages\EditOperationCondition::route('/{record}/edit'),
        ];
    }
}
