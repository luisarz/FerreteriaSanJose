<?php

namespace App\Filament\Resources\OperationConditions;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\OperationConditions\Pages\ListOperationConditions;
use App\Filament\Resources\OperationConditions\Pages\CreateOperationCondition;
use App\Filament\Resources\OperationConditions\Pages\EditOperationCondition;
use App\Filament\Resources\OperationConditionResource\Pages;
use App\Filament\Resources\OperationConditionResource\RelationManagers;
use App\Models\OperationCondition;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OperationConditionResource extends Resource
{
    protected static ?string $model = OperationCondition::class;

    protected static ?string $label = 'Cat-016 Condiciones de operacióne';
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 16;
    public static function getNavigationLabel(): string
    {
        return substr(static::$label, 0, -1);
    }
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
               Section::make('Información Condición de operación')
                   ->description('Información de la condición de operación')
                   ->icon('heroicon-o-credit-card')
                   ->iconColor('info')
                   ->columns(2)
                   ->compact()
                 ->schema([
                     TextInput::make('code')
                         ->label('Código')
                         ->required()
                         ->maxLength(255),
                     TextInput::make('name')
                         ->label('Condición de operación')
                         ->required()
                         ->maxLength(255),
                     Toggle::make('is_active')
                         ->label('Activo')
                         ->required(),
                     ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Condición de operación')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creado')
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
                ActionGroup::make([
                    ViewAction::make()->label('Ver'),
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
            'index' => ListOperationConditions::route('/'),
            'create' => CreateOperationCondition::route('/create'),
            'edit' => EditOperationCondition::route('/{record}/edit'),
        ];
    }
}
