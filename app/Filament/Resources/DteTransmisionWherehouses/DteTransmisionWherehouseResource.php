<?php

namespace App\Filament\Resources\DteTransmisionWherehouses;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Resources\DteTransmisionWherehouses\Pages\ListDteTransmisionWherehouses;
use App\Filament\Resources\DteTransmisionWherehouseResource\Pages;
use App\Filament\Resources\DteTransmisionWherehouseResource\RelationManagers;
use App\Models\DteTransmisionWherehouse;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DteTransmisionWherehouseResource extends Resource
{
    protected static ?string $model = DteTransmisionWherehouse::class;
    protected static ?string $label = 'Transmision DTE';
    protected static ?string $pluralLabel = 'Impresión DTE Sucursal';
    protected static string | \UnitEnum | null $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 3;


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
               Section::make('Configuracion de transmision DTE')
                   ->compact()
                   ->columns(2)
                ->schema([
                    Select::make('wherehouse')
                        ->label('Sucursal')
                        ->relationship('where_house', 'name')
                        ->preload()
                        ->default(function () {
                            return auth()->user()->employee->branch_id;
                        })
                        ->required(),
                    Select::make('billing_model')
                        ->label('Modelo de Facturación')
                        ->relationship('billingModel', 'name')
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($get, $set){

                        })
                        ->default(1)
                        ->required(),
                    Select::make('transmision_type')
                        ->label('Tipo de Transmisión')
                        ->relationship('transmisionType', 'name')
                        ->preload()
                        ->default(1)
                        ->required(),
                    Select::make('printer_type')
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
                TextColumn::make('where_house.name')
                    ->label('Sucursal')
                    ->sortable(),
                TextColumn::make('billingModel.name')
                    ->label('Modelo de Facturación')
                    ->sortable(),
                TextColumn::make('transmisionType.name')
                    ->label('Tipo de Transmisión')
                    ->sortable(),
                TextColumn::make('printer_type')
                    ->label('Tipo de Impresión')
                    ->formatStateUsing(fn ($state) => $state == 1 ? 'Ticket' : 'PDF')
                    ->sortable(),

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
            'index' => ListDteTransmisionWherehouses::route('/'),
//            'create' => Pages\CreateDteTransmisionWherehouse::route('/create'),
//            'edit' => Pages\EditDteTransmisionWherehouse::route('/{record}/edit'),
        ];
    }
}
