<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CashboxResource\Pages;
use App\Filament\Resources\CashboxResource\RelationManagers;
use App\Filament\Resources\CashBoxResource\RelationManagers\CorrelativesRelationManager;
use App\Models\CashBox;
use App\Models\CashBoxOpen;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CashboxResource extends Resource
{
    protected static ?string $model = CashBox::class;

    protected static ?string $label = 'Cajas';
    protected static  ?string $navigationGroup="Configuración";
    protected static  ?int $navigationSort=3;
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('')
                ->schema([
                    Forms\Components\Select::make('branch_id')
                        ->relationship('branch', 'name')
                        ->label('Sucursal')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('description')
                        ->label('Descripción')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\TextInput::make('balance')
                        ->required()
                        ->numeric(),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Activa')
                        ->default(true),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable(),
                Tables\Columns\TextColumn::make('balance')
                   ->money('USD', locale: 'en_US')
                    ->label('Saldo')
                    ->badge(fn ($record) => $record->balance < 100 ? 'danger' : 'success')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_open')
                    ->label('Abierta')

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
            ->recordUrl(null)
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->visible(function ($record) {
                    return !$record->is_open;
                }),
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
            CorrelativesRelationManager::class ,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashboxes::route('/'),
            'create' => Pages\CreateCashbox::route('/create'),
            'edit' => Pages\EditCashbox::route('/{record}/edit'),
        ];
    }
}
