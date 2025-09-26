<?php

namespace App\Filament\Resources\PersonTypes;

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
use App\Filament\Resources\PersonTypes\Pages\ListPersonTypes;
use App\Filament\Resources\PersonTypeResource\Pages;
use App\Filament\Resources\PersonTypeResource\RelationManagers;
use App\Models\personType;
use Filament\Forms;
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
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Tipo Cliente')
                ->compact()
                ->schema([
                    TextInput::make('code')
                        ->label('Código')
                        ->required()
                        ->maxLength(5),
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(150),
                    Toggle::make('is_active')
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
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('name')
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
//                Tables\Actions\ViewAction::make()->label('')->iconSize(IconSize::Medium),
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
            'index' => ListPersonTypes::route('/'),
//            'create' => Pages\CreatePersonType::route('/create'),
//            'edit' => Pages\EditPersonType::route('/{record}/edit'),
        ];
    }
}
