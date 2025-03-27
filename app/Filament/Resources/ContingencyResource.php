<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContingencyResource\Pages;
use App\Filament\Resources\ContingencyResource\RelationManagers;
use App\Http\Controllers\ContingencyController;
use App\Models\Contingency;
use EightyNine\FilamentPageAlerts\PageAlert;
use Filament\Forms;
use Filament\Forms\Components\Select;
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
//                Tables\Actions\Action::make()
                 Tables\Actions\Action::make('close')
                    ->label('Cerrar')
                    ->icon('heroicon-o-plus-circle')
                    ->requiresConfirmation()
                    ->visible(function ($record) {
                        return $record->is_close == 0;
                    })

                    ->modalSubmitActionLabel('Cerrar Contingencia')
                    ->form([

                            Select::make('confirmacion')
                                ->label('Confirmar')
                                ->inlineLabel(false)
                                ->options(['si' => 'Sí, deseo Cerrar', 'no' => 'No, no enviar'])
                                ->required(),

                        ]
                    )
                    ->action(function ($record, array $data) {
                        if ($data['confirmacion'] === 'si') {
                            $dteController = new ContingencyController();
                            if($record->uuid_hacienda == null){
                                PageAlert::make()
                                    ->title('No se puede cerrar la contingencia')
                                    ->danger()
                                    ->send();
                            }
                            $resultado = $dteController->contingencyCloseDTE($record->uuid_hacienda);
                            if($resultado){
                                PageAlert::make()
                                    ->title('Contingencia generada Exitosa')
                                    ->success()
                                    ->send();
                            }else{
                                PageAlert::make()
                                    ->title('Fallo en envío')
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            PageAlert::make()
                                ->title('Se canceló el envío')
                                ->warning()
                                ->send();
                        }
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContingencies::route('/'),
//            'create' => Pages\CreateContingency::route('/create'),
//            'edit' => Pages\EditContingency::route('/{record}/edit'),
        ];
    }
}
