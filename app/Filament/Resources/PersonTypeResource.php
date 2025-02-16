<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PersonTypeResource\Pages;
use App\Filament\Resources\PersonTypeResource\RelationManagers;
use App\Models\personType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PersonTypeResource extends Resource
{
    protected static ?string $model = personType::class;
    protected static ?string $label = 'Cat-029 Tipo Persona';
    protected static ?int $navigationSort = 29;
    protected static ?string $navigationGroup = 'Catálogos Hacienda';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Tipo Cliente')
                ->compact()
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->label('Código')
                        ->required()
                        ->maxLength(5),
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(150),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true)
                        ->required(),
                ])

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
//                Tables\Actions\ViewAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\EditAction::make()->label('')->iconSize(IconSize::Medium),
                Tables\Actions\DeleteAction::make()->label('')->iconSize(IconSize::Medium),
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
            'index' => Pages\ListPersonTypes::route('/'),
//            'create' => Pages\CreatePersonType::route('/create'),
//            'edit' => Pages\EditPersonType::route('/{record}/edit'),
        ];
    }
}
