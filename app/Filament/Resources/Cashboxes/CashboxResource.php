<?php

namespace App\Filament\Resources\Cashboxes;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\Cashboxes\Pages\ListCashboxes;
use App\Filament\Resources\Cashboxes\Pages\CreateCashbox;
use App\Filament\Resources\Cashboxes\Pages\EditCashbox;
use App\Filament\Resources\CashboxResource\Pages;
use App\Filament\Resources\CashboxResource\RelationManagers;
use App\Filament\Resources\Cashboxes\RelationManagers\CorrelativesRelationManager;
use App\Models\CashBox;
use App\Models\CashBoxOpen;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashboxResource extends Resource
{
    protected static ?string $model = CashBox::class;

    protected static ?string $label = 'Cajas';
    protected static  string | \UnitEnum | null $navigationGroup="Configuración";
    protected static  ?int $navigationSort=3;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('')
                ->schema([
                    Select::make('branch_id')
                        ->relationship('branch', 'name')
                        ->label('Sucursal')
                        ->searchable()
                        ->preload()
                        ->required(),

                    TextInput::make('description')
                        ->label('Descripción')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('balance')
                        ->required()
                        ->numeric(),
                    Toggle::make('is_active')
                        ->label('Activa')
                        ->default(true),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable(),
                TextColumn::make('balance')
                   ->money('USD', locale: 'en_US')
                    ->label('Saldo')
                    ->badge(fn ($record) => $record->balance < 100 ? 'danger' : 'success')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
                IconColumn::make('is_open')
                    ->label('Abierta')

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
            ->recordUrl(null)
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                ->visible(function ($record) {
                    return !$record->is_open;
                }),
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
            CorrelativesRelationManager::class ,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashboxes::route('/'),
            'create' => CreateCashbox::route('/create'),
            'edit' => EditCashbox::route('/{record}/edit'),
        ];
    }
}
