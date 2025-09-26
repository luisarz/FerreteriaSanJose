<?php

namespace App\Filament\Resources\BillingModels;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\BillingModels\Pages\ListBillingModels;
use App\Filament\Resources\BillingModelResource\Pages;
use App\Filament\Resources\BillingModelResource\RelationManagers;
use App\Models\BillingModel;
use Filament\Forms;
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
protected static string | \UnitEnum | null $navigationGroup = 'Catálogos Hacienda';
protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                    Section::make('')
                        ->compact()
                        ->schema([
                            TextInput::make('code')
                                ->label('Código')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('name')
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
            'index' => ListBillingModels::route('/'),
//            'create' => Pages\CreateBillingModel::route('/create'),
//            'edit' => Pages\EditBillingModel::route('/{record}/edit'),
        ];
    }
}
