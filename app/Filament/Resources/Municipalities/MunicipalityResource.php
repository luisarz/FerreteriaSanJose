<?php

namespace App\Filament\Resources\Municipalities;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ViewAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Municipalities\Pages\ListMunicipalities;
use App\Filament\Resources\MunicipalityResource\Pages;
use App\Filament\Resources\MunicipalityResource\RelationManagers;
use App\Models\Municipality;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MunicipalityResource extends Resource
{
    protected static ?string $model = Municipality::class;

    protected static bool $softDelete = true;
    protected static string | \UnitEnum | null $navigationGroup = "Catálogos Hacienda";
    protected static ?string $label = 'Distritos';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 4;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de Municipio')
                    ->schema([
                        TextInput::make('code')
                            ->label('Código')  // Etiqueta opcional
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label('Distrito')  // Etiqueta opcional
                            ->required()
                            ->maxLength(255),
                        Select::make('distrito_id')
                            ->label('Municipio')  // Etiqueta opcional
                            ->relationship('distrito', 'name')  // Relación con el modelo 'distrito'
                            ->preload()  // Pre-carga las opciones para optimizar
                            ->searchable()  // Permite búsqueda en el select
                            ->required(),
                        Toggle::make('is_active')
                            ->label('¿Está Activo?')  // Etiqueta opcional para mayor claridad
                            ->required(),
                    ])
                    ->columns(2),  // Define que los campos se dividan en 2 columnas
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->wrap()
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $record->deleted_at
                        ? "<span style='text-decoration: line-through; color: red;'>".strtoupper($state)."</span>"
                        : strtoupper($state)) // Convierte a mayúsculas
                    ->html(),
                TextColumn::make('distrito.name')
                    ->label('Municipio')
                    ->numeric()
                    ->sortable(),
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
//               Tables\actions\actiongroup::make([
                   ViewAction::make()->label('')->iconSize(IconSize::Medium)->tooltip('Ver'),
                   ReplicateAction::make()->label('')->iconSize(IconSize::Medium)->tooltip('Duplicar')->color('success'),
                   EditAction::make()->label('')->iconSize(IconSize::Medium)->tooltip('Editar'),
                   DeleteAction::make()->label('')->iconSize(IconSize::Medium)->tooltip('Eliminar'),
                   RestoreAction::make()->label('')->iconSize(IconSize::Medium)->tooltip('Restaurar'),
//                ]),
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
            'index' => ListMunicipalities::route('/'),
//            'create' => Pages\CreateMunicipality::route('/create'),
//            'edit' => Pages\EditMunicipality::route('/{record}/edit'),
        ];
    }
}
