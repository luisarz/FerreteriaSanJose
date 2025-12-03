<?php

namespace App\Filament\Resources\Categories;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use CharrafiMed\GlobalSearchModal\Customization\Position;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $label = 'Categorías';
    protected static ?bool $softDelete = true;
    protected static string | \UnitEnum | null $navigationGroup = 'Almacén';
    protected static ?string $recordTitleAttribute = 'name';


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información de la categoría')
                    ->schema([
                        TextInput::make('name')
                            ->label('Categoría de producto') // Corregido el acento en "producto"
                            ->required()
                            ->maxLength(255),
                        Select::make('parent_id')
                            ->relationship('category', 'name')
                            ->nullable()
                            ->placeholder('Seleccione una categoría')
                            ->preload()
                            ->searchable()
                            ->label('Categoría padre'),
                        Toggle::make('is_active')
                            ->label('Activo') // Agregué un label para darle más claridad al toggle
                            ->required(),
                        TextInput::make('commission_percentage')
                            ->label('Comisión por venta') // Corregido el acento en "producto"
                            ->required()
                            ->numeric(),
                    ])->columns(2),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Código')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Categoría de producto') // Corregido el acento en "producto"
                    ->searchable(),
                TextColumn::make('category.name')
                    ->label('Categoría padre')
                    ->placeholder('Ninguna')
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('Productos')
                    ->counts('products')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('commission_percentage')
                    ->suffix('%')
                    ->label('Comisión por venta') // Corregido el acento en "producto"
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
                //
            ])
            ->recordActions([
                EditAction::make()->color('primary')->label('')->iconSize(IconSize::Medium),
                ReplicateAction::make()->color('success')->label('')->iconSize(IconSize::Medium),
                DeleteAction::make()->color('danger')->label('')->iconSize(IconSize::Medium),
            ], position: RecordActionsPosition::BeforeColumns)
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
            'index' => ListCategories::route('/'),
//            'create' => Pages\CreateCategory::route('/create'),
//            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
