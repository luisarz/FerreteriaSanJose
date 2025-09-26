<?php

namespace App\Filament\Resources\SmallCashBoxOperations\Pages;

use App\Filament\Resources\SmallCashBoxOperations\SmallCashBoxOperationResource;
use App\Models\CashBox;
use App\Models\SmallCashBoxOperation;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSmallCashBoxOperation extends CreateRecord
{
    protected static string $resource = SmallCashBoxOperationResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    public function beforeCreate()
    {
        $operationType = $this->data['operation'];
        $amount = $this->data['amount'];
        $caja = SmallCashBoxOperation::with('cashBoxOpen')->first();
        if (!$caja) {
            Notification::make()
                ->title('No hay caja abierta')
                ->body('No se puede realizar la operación')
                ->danger()
                ->icon('x-circle')
                ->send();
                $this->halt()->stop();
        }
        $cashBox = $caja->cashBoxOpen->cashbox;
        if ($operationType === 'Egreso') {
            if ($cashBox->balance < $amount) {
                Notification::make()
                    ->title('Fondos insuficientes')
                    ->body('No se puede realizar la operación')
                    ->danger()
                    ->iconColor('danger')
                    ->icon('heroicon-o-x-circle')
                    ->send();
                $this->halt()->stop();
            }
            $cashBox->balance -= $amount;
        } elseif ($operationType === 'Ingreso') {
            $cashBox->balance += $amount;
        }

        // Guardar el nuevo balance
        $cashBox->save();
    }

}
