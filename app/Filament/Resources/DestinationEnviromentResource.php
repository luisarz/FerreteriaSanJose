<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinationEnviromentResource\Pages;
use App\Filament\Resources\DestinationEnviromentResource\RelationManagers;
use App\Models\DestinationEnviroment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconSize;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DestinationEnviromentResource extends Resource
{
    protected static ?string $model = DestinationEnviroment::class;

    protected static ?string $label="Cat-001 Ambiente de Destino";
    protected static ?string $navigationGroup = 'Catálogos Hacienda';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
               Forms\Components\Section::make('')
                ->compact()
                ->schema([
                    Forms\Components\TextInput::make('code')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255),
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
                Tables\Actions\EditAction::make()->label('')->iconSize(IconSize::Medium),
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
            'index' => Pages\ListDestinationEnviroments::route('/'),
//            'create' => Pages\CreateDestinationEnviroment::route('/create'),
//            'edit' => Pages\EditDestinationEnviroment::route('/{record}/edit'),
        ];
    }
}
