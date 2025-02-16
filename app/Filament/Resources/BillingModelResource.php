<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BillingModelResource\Pages;
use App\Filament\Resources\BillingModelResource\RelationManagers;
use App\Models\BillingModel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BillingModelResource extends Resource
{
    protected static ?string $model = BillingModel::class;
protected static ?string $label = 'CAT-003 Modelo de Facturación';
protected static ?string $pluralLabel = 'CAT-003 Modelos de Facturación';
protected static ?string $navigationGroup = 'Catálogos Hacienda';
protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                    Forms\Components\Section::make('')
                        ->compact()
                        ->schema([
                            Forms\Components\TextInput::make('code')
                                ->label('Código')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->label('Nombre')
                                ->maxLength(255),
                            ]),
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
                Tables\Actions\EditAction::make(),
            ])
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
            'index' => Pages\ListBillingModels::route('/'),
//            'create' => Pages\CreateBillingModel::route('/create'),
//            'edit' => Pages\EditBillingModel::route('/{record}/edit'),
        ];
    }
}
