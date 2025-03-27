<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContingencyResource\Pages;
use App\Filament\Resources\ContingencyResource\RelationManagers;
use App\Models\Contingency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ContingencyResource extends Resource
{
    protected static ?string $model = Contingency::class;

    protected static ?string $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 4;
    protected static ?string $label = 'Trans. Contingencia';


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
//                Tables\Columns\TextColumn::make('id')
//                    ->label('ID')
//                    ->searchable(),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Sucursal')
                    ->sortable(),
                Tables\Columns\TextColumn::make('uuid_hacienda')
                    ->label('Hacienda')
                    ->sortable(),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('Fecha Fin')
                    ->placeholder('Fecha Inicio')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('is_close')
                    ->extraAttributes(['class' => 'text-lg'])  // Cambia el tamaño de la fuente
                    ->label('Estado Contingencia')
                    ->tooltip(fn ($state) => $state === 1 ? 'Cerrada' : 'Abierto')
                    ->icon(fn ($state) => $state === 1 ? 'heroicon-o-clock' : 'heroicon-o-check-circle')
                    ->color(fn ($state) => $state === 1 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Cerrada' : 'Abierta'),


        Tables\Columns\TextColumn::make('contingencyType.name')
                    ->label('Tipo de Contingencia')
                    ->sortable(),
                Tables\Columns\TextColumn::make('contingency_motivation')
                    ->label('Motivo')
                    ->placeholder('Sin motivo')
                    ->searchable(),

            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make('Cerrar'),
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
            'index' => Pages\ListContingencies::route('/'),
            'create' => Pages\CreateContingency::route('/create'),
            'edit' => Pages\EditContingency::route('/{record}/edit'),
        ];
    }
}
