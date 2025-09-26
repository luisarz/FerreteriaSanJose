<?php

namespace App\Filament\Resources\StablishmentTypes;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\StablishmentTypes\Pages\ListStablishmentTypes;
use App\Filament\Resources\StablishmentTypeResource\Pages;
use App\Filament\Resources\StablishmentTypeResource\RelationManagers;
use App\Models\StablishmentType;
use Filament\Forms;
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
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 9;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([


                Section::make('Información Tipo de Establecimiento')
                    ->compact()
                    ->columns(1)
                    ->schema([

                        TextInput::make('code')
                            ->label('Código')
                            ->maxLength(255)
                            ->default(null),
                        TextInput::make('name')
                            ->label('Tipo de establecimiento')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
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
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
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
                TrashedFilter::make(),
            ])
            ->recordActions([
//                Tables\Actions\ActionGroup::make( [
                ViewAction::make()->label('')->iconSize(IconSize::Medium),
                EditAction::make()->label('')->iconSize(IconSize::Medium),
                ReplicateAction::make()->label('')->iconSize(IconSize::Medium)->color('success'),
                DeleteAction::make()->label('')->iconSize(IconSize::Medium),
                RestoreAction::make()->label('')->iconSize(IconSize::Medium),
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
            'index' => ListStablishmentTypes::route('/'),
//            'create' => Pages\CreateStablishmentType::route('/create'),
//            'edit' => Pages\EditStablishmentType::route('/{record}/edit'),
        ];
    }
}
