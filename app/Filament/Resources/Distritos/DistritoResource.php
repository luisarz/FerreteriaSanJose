<?php

namespace App\Filament\Resources\Distritos;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Distritos\Pages\ListDistritos;
use App\Filament\Resources\DistritoResource\Pages;
use App\Filament\Resources\DistritoResource\RelationManagers;
use App\Models\Distrito;
use Filament\Forms;
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
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 13;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información del Municipio')
                    ->compact()
                    ->schema([
                        TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label('Municipio')
                            ->required()
                            ->maxLength(255),
                        Select::make('departamento_id')
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
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Municipio')
                    ->searchable(),
                TextColumn::make('departamento.name')
                    ->sortable(),
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
                SelectFilter::make('departamento_id')
                    ->relationship('departamento', 'name')
                    ->preload()
                    ->searchable()
                    ->label('Departamento')
                    ->default(''),
            ])
            ->recordActions([
//                Tables\Actions\ActionGroup::make([
                EditAction::make()->label('')->iconSize(IconSize::Medium),
                ReplicateAction::make()->label('')->iconSize(IconSize::Medium)->color('success'),
                DeleteAction::make()->label('')->iconSize(IconSize::Medium),
//                ]),
//                Tables\Actions\ViewAction::make()->label('')->iconSize(IconSize::Medium),
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
            'index' => ListDistritos::route('/'),
//            'create' => Pages\CreateDistrito::route('/create'),
//            'edit' => Pages\EditDistrito::route('/{record}/edit'),
        ];
    }
}
