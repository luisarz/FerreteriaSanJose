<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use CharrafiMed\GlobalSearchModal\Customization\Position;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Enums\ActionsPosition;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;
    protected static ?string $label = 'Categorías';
    protected static ?bool $softDelete = true;
    protected static ?string $navigationGroup = 'Almacén';
    protected static ?string $recordTitleAttribute = 'name';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la categoría')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Categoría de producto') // Corregido el acento en "producto"
                            ->required()
                            ->maxLength(255),
                        Forms\Components\BelongsToSelect::make('parent_id')
                            ->relationship('category', 'name')
                            ->nullable()
                            ->placeholder('Seleccione una categoría')
                            ->preload()
                            ->searchable()
                            ->label('Categoría padre'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo') // Agregué un label para darle más claridad al toggle
                            ->required(),
                        Forms\Components\TextInput::make('commission_percentage')
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
                Tables\Columns\TextColumn::make('id')
                    ->label('Código')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Categoría de producto') // Corregido el acento en "producto"
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Categoría padre')
                    ->placeholder('Ninguna')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('commission_percentage')
                    ->suffix('%')
                    ->label('Comisión por venta') // Corregido el acento en "producto"
                    ->sortable(),
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
                Tables\Actions\EditAction::make()->color('primary')->label('')->iconSize(IconSize::Medium),
                Tables\Actions\ReplicateAction::make()->color('success')->label('')->iconSize(IconSize::Medium),
                Tables\Actions\DeleteAction::make()->color('danger')->label('')->iconSize(IconSize::Medium),
            ], position: ActionsPosition::BeforeColumns)
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
            'index' => Pages\ListCategories::route('/'),
//            'create' => Pages\CreateCategory::route('/create'),
//            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
