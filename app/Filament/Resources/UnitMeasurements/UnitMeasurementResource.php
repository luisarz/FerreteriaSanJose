<?php

namespace App\Filament\Resources\UnitMeasurements;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\UnitMeasurements\Pages\ListUnitMeasurements;
use App\Filament\Resources\UnitMeasurementResource\Pages;
use App\Filament\Resources\UnitMeasurementResource\RelationManagers;
use App\Models\UnitMeasurement;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UnitMeasurementResource extends Resource
{
    protected static ?string $model = UnitMeasurement::class;

    protected static  ?string $label= 'Cat-014 U. Medida';
    protected static ?bool $softDelete = true;
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 14;

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
                        ->label('Descripción')
                        ->required()
                        ->maxLength(255),
                    Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true)
                        ->required(),
                ])->columns(1)
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
                IconColumn::make('is_active')
                    ->boolean(),
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
            'index' => ListUnitMeasurements::route('/'),
//            'create' => Pages\CreateUnitMeasurement::route('/create'),
//            'edit' => Pages\EditUnitMeasurement::route('/{record}/edit'),
        ];
    }
}
