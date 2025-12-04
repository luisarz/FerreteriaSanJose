<?php

namespace App\Filament\Resources\Contingencies;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use App\Filament\Resources\Contingencies\Pages\ListContingencies;
use App\Filament\Resources\ContingencyResource\Pages;
use App\Filament\Resources\ContingencyResource\RelationManagers;
use App\Http\Controllers\ContingencyController;
use App\Models\Contingency;
use Carbon\Carbon;
use EightyNine\FilamentPageAlerts\PageAlert;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
class ContingencyResource extends Resource
{
    protected static ?string $model = Contingency::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Configuración';
    protected static ?int $navigationSort = 4;
    protected static ?string $label = 'Trans. Contingencia';


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
//                Tables\Columns\TextColumn::make('id')
//                    ->label('ID')
//                    ->searchable(),

                TextColumn::make('warehouse.name')
                    ->label('Sucursal')
                    ->sortable(),
                TextColumn::make('uuid_hacienda')
                    ->label('Hacienda')
                    ->limit(15)
                    ->copyable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Fecha Inicio')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Fecha Fin')
                    ->placeholder('Fecha Inicio')
                    ->dateTime()
                    ->sortable(),

                BadgeColumn::make('is_close')
                    ->extraAttributes(['class' => 'text-lg'])  // Cambia el tamaño de la fuente
                    ->label('Estado')

                    ->tooltip(fn ($state) => $state === 1 ? 'Cerrada' : 'Abierto')
                    ->icon(fn ($state) => $state === 1 ? 'heroicon-o-clock' : 'heroicon-o-check-circle')
                    ->color(fn ($state) => $state === 1 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state === 1 ? 'Cerrada' : 'Abierta'),


                TextColumn::make('contingencyType.name')
                    ->label('Tipo')
                    ->limit(15)
                    ->tooltip(fn ($state) => $state ? $state : 'Nombre no disponible')  // Mostrar el nombre directamente en el tooltip
                    ->sortable(),

                TextColumn::make('contingency_motivation')
                    ->label('Motivo')
                    ->limit(15)
                    ->tooltip(fn ($state) => $state ? $state : 'Nombre no disponible')  // Mostrar el nombre directamente en el tooltip

                    ->placeholder('Sin motivo')
                    ->searchable(),

            ])  ->modifyQueryUsing(fn($query) => $query
                ->orderByDesc('created_at')
            )
            ->filters([

            ])
            ->recordActions([
//                Tables\Actions\Action::make()
                Action::make('close')
                    ->label('Cerrar')
                    ->icon('heroicon-o-plus-circle')
                    ->requiresConfirmation()
                    ->visible(function ($record) {
                        return $record->is_close == 0;
                    })

                    ->modalSubmitActionLabel('Cerrar Contingencia')
                    ->schema([

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
                                Notification::make()
                                    ->title('No se puede cerrar la contingencia')
                                    ->danger()
                                    ->send();
                            }
                            $resultado = $dteController->contingencyCloseDTE($record->uuid_hacienda);
                            dd($resultado);
                            if($resultado){
                                Notification::make()
                                    ->title('Contingencia generada Exitosa')
                                    ->success()
                                    ->send();
                            }else{
                                Notification::make()
                                    ->title('Fallo en envío')
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            Notification::make()
                                ->title('Se canceló el envío')
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
//                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => ListContingencies::route('/'),
//            'create' => Pages\CreateContingency::route('/create'),
//            'edit' => Pages\EditContingency::route('/{record}/edit'),
        ];
    }
}
