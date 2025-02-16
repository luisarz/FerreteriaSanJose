<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MunicipalityResource\Pages;
use App\Filament\Resources\MunicipalityResource\RelationManagers;
use App\Models\Municipality;
use Filament\Forms;
use Filament\Forms\Form;
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
    protected static ?string $navigationGroup = "Catálogos Hacienda";
    protected static ?string $label = 'Distritos';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 4;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de Municipio')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Código')  // Etiqueta opcional
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name')
                            ->label('Distrito')  // Etiqueta opcional
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('distrito_id')
                            ->label('Municipio')  // Etiqueta opcional
                            ->relationship('distrito', 'name')  // Relación con el modelo 'distrito'
                            ->preload()  // Pre-carga las opciones para optimizar
                            ->searchable()  // Permite búsqueda en el select
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
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
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->wrap()
                    ->searchable()
                    ->formatStateUsing(fn ($state, $record) => $record->deleted_at
                        ? "<span style='text-decoration: line-through; color: red;'>".strtoupper($state)."</span>"
                        : strtoupper($state)) // Convierte a mayúsculas
                    ->html(),
                Tables\Columns\TextColumn::make('distrito.name')
                    ->label('Municipio')
                    ->numeric()
                    ->sortable(),
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
//               Tables\actions\actiongroup::make([
                   Tables\Actions\ViewAction::make()->label('')->iconSize(IconSize::Medium)->tooltip('Ver'),
                   Tables\Actions\ReplicateAction::make()->label('')->iconSize(IconSize::Medium)->tooltip('Duplicar')->color('success'),
                   Tables\Actions\EditAction::make()->label('')->iconSize(IconSize::Medium)->tooltip('Editar'),
                   Tables\Actions\DeleteAction::make()->label('')->iconSize(IconSize::Medium)->tooltip('Eliminar'),
                   Tables\Actions\RestoreAction::make()->label('')->iconSize(IconSize::Medium)->tooltip('Restaurar'),
//                ]),
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
            'index' => Pages\ListMunicipalities::route('/'),
//            'create' => Pages\CreateMunicipality::route('/create'),
//            'edit' => Pages\EditMunicipality::route('/{record}/edit'),
        ];
    }
}
