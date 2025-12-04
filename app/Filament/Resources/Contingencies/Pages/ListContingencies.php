<?php

namespace App\Filament\Resources\Contingencies\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\Contingencies\ContingencyResource;
use App\Http\Controllers\ContingencyController;
use App\Http\Controllers\DTEController;
use App\Models\Contingency;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Forms;
use Filament\Forms\Form;

class ListContingencies extends ListRecords
{
    protected static string $resource = ContingencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('Nueva Contingencia')
                ->icon('heroicon-o-plus-circle')
                ->requiresConfirmation()
                ->visible(function () {
                    $branchId = auth()->user()->employee->branch_id ?? null;
                    if (!$branchId) {
                        return false;
                    }
                    // Check if there's an open contingency for this branch
                    return !Contingency::where('warehouse_id', $branchId)
                        ->where('is_close', 0)
                        ->exists();
                })

                ->modalSubmitActionLabel('Generar Contingencia')
                ->schema([

                        TextInput::make('description')
                            ->label('Motivo')
                            ->inlineLabel(false)
                            ->required()
                            ->maxLength(255),
                        Select::make('confirmacion')
                            ->label('Confirmar')
                            ->inlineLabel(false)
                            ->options(['si' => 'Sí, deseo Generar', 'no' => 'No, no enviar'])
                            ->required(),

                    ]
                )
                ->action(function ($record, array $data) {
                    if ($data['confirmacion'] === 'si') {
                        $dteController = new ContingencyController();
                        $descripcion = $data['description'];
                        $resultado = $dteController->contingencyDTE($descripcion);
//                        dd($resultado);
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
        ];
    }
}
