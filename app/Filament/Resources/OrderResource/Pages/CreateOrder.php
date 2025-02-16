<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\SaleItem;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return '';
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Orden Iniciada')
            ->body('La orden fue Iniciada puedes agregar productos o servicios a la orden')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Iniciar Orden')
                ->color('success')
                ->icon('heroicon-o-check')
                ->action('create')
                ->before(function (Action $action, array &$data) {
                    $data['is_order'] = true;
                    $data['is_invoiced_order'] = false;
                    dd($data);
                })
                ->extraAttributes([
                    'class' => 'alig', // Tailwind para ajustar el margen alinearlo a la derecha

                ]),

            Action::make('cancelSale')
                ->label('Cancelar proceso')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Confirmación!!')
                ->modalSubheading('¿Estás seguro de que deseas cancelar esta venta? Esta acción no se puede deshacer.')
                ->modalButton('Sí, cancelar venta')
                ->action(function (Actions\DeleteAction $delete) {
                    if ($this->record->is_dte) {
                        Notification::make('No se puede cancelar una venta con DTE')
                            ->title('Error al anular venta')
                            ->body('No se puede cancelar una venta con DTE')
                            ->danger()
                            ->send();
                        return;
                    }
                    $this->record->delete();
                    SaleItem::where('sale_id', $this->record->id)->delete();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_order'] = true;
        $data['is_invoiced_order'] = false;
        $whereHouse = auth()->user()->employee->branch_id ?? null;
        $lastOrder = \App\Models\Sale::where('wherehouse_id', $whereHouse)
            ->max('order_number');
        $nextNumber = $lastOrder ? intval(preg_replace('/[^0-9]/', '', $lastOrder)) + 1 : 1;
        $data['order_number'] = str_pad($nextNumber, 9, '0', STR_PAD_LEFT);
        return $data; // Devuelve los datos modificados

    }


}
