<?php

namespace App\Filament\Resources\TransmisionTypes;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\TransmisionTypes\Pages\ListTransmisionTypes;
use App\Filament\Resources\TransmisionTypeResource\Pages;
use App\Filament\Resources\TransmisionTypeResource\RelationManagers;
use App\Models\TransmisionType;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransmisionTypeResource extends Resource
{
    protected static ?string $model = TransmisionType::class;
    protected static ?string $label = 'CAT-004 Tipo de Transmisión';
    protected static ?string $pluralLabel = 'CAT-004 Tipos de Transmisión';
    protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 4;


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                    ->columns(1)
                    ->schema([
                        TextInput::make('code')
                            ->label('Código')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('name')
                            ->label('Nombre')
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
                TextColumn::make('name')
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
                EditAction::make(),
            ])
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
            'index' => ListTransmisionTypes::route('/'),
//            'create' => Pages\CreateTransmisionType::route('/create'),
//            'edit' => Pages\EditTransmisionType::route('/{record}/edit'),
        ];
    }
}
