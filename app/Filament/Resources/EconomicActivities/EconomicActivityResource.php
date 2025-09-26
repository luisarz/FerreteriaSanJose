<?php

namespace App\Filament\Resources\EconomicActivities;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\EconomicActivities\Pages\ListEconomicActivities;
use App\Filament\Resources\EconomicActivityResource\Pages;
use App\Filament\Resources\EconomicActivityResource\RelationManagers;
use App\Models\EconomicActivity;
use Filament\Forms;
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
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 19;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                ->schema([
                    TextInput::make('code')
                        ->label('Código')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('description')
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
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('description')
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
                EditAction::make()->label('')->iconSize(IconSize::Medium),
                DeleteAction::make()->label('')->iconSize(IconSize::Medium),
            ],position: RecordActionsPosition::BeforeColumns)
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
            'index' => ListEconomicActivities::route('/'),
//            'create' => Pages\CreateEconomicActivity::route('/create'),
//            'edit' => Pages\EditEconomicActivity::route('/{record}/edit'),
        ];
    }
}
