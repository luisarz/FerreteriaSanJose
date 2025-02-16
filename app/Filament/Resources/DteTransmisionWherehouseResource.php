<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DteTransmisionWherehouseResource\Pages;
use App\Filament\Resources\DteTransmisionWherehouseResource\RelationManagers;
use App\Models\DteTransmisionWherehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DteTransmisionWherehouseResource extends Resource
{
    protected static ?string $model = DteTransmisionWherehouse::class;
    protected static ?string $label = 'Transmision DTE';
    protected static ?string $pluralLabel = 'Transmisión DTE Sucursal';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 3;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
               Forms\Components\Section::make('Configuracion de transmision DTE')
                   ->compact()
                   ->columns(2)
                ->schema([
                    Forms\Components\Select::make('wherehouse')
                        ->label('Sucursal')
                        ->relationship('where_house', 'name')
                        ->preload()
                        ->default(function () {
                            return auth()->user()->employee->branch_id;
                        })
                        ->required(),
                    Forms\Components\Select::make('billing_model')
                        ->label('Modelo de Facturación')
                        ->relationship('billingModel', 'name')
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($get, $set){

                        })
                        ->default(1)
                        ->required(),
                    Forms\Components\Select::make('transmision_type')
                        ->label('Tipo de Transmisión')
                        ->relationship('transmisionType', 'name')
                        ->preload()
                        ->default(1)
                        ->required(),
                    Forms\Components\Select::make('printer_type')
                        ->options([
                            1 => 'Ticket',
                            2 => 'PDF',
                        ])
                        ->required()
                        ->default(1),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('where_house.name')
                    ->label('Sucursal')
                    ->sortable(),
                Tables\Columns\TextColumn::make('billingModel.name')
                    ->label('Modelo de Facturación')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transmisionType.name')
                    ->label('Tipo de Transmisión')
                    ->sortable(),
                Tables\Columns\TextColumn::make('printer_type')
                    ->label('Tipo de Impresión')
                    ->formatStateUsing(fn ($state) => $state == 1 ? 'Ticket' : 'PDF')
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
            'index' => Pages\ListDteTransmisionWherehouses::route('/'),
//            'create' => Pages\CreateDteTransmisionWherehouse::route('/create'),
//            'edit' => Pages\EditDteTransmisionWherehouse::route('/{record}/edit'),
        ];
    }
}
