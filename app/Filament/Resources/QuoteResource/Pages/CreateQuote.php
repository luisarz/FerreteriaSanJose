<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Filament\Resources\QuoteResource;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;
    protected static bool $canCreateAnother = false;

    public function getTitle(): string
    {
        return '';
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cotización Iniciada')
            ->body('La Cotización fue Iniciada puedes agregar productos o servicios a la orden')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Iniciar Cotización')
                ->color('success')
                ->icon('heroicon-o-check')
                ->action('create')
                ->before(function (Action $action, array &$data) {
                    $data['operation_type'] = "Quote";
                    $data['is_invoiced'] = false;
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
        $data['operation_type'] = "Quote";
        $data['is_invoiced'] = false;
        $whereHouse = auth()->user()->employee->branch_id ?? null;
        $lastOrder = Sale::where('wherehouse_id', $whereHouse)
            ->where('operation_type', 'Quote')
            ->max('order_number');
        $nextNumber = $lastOrder ? intval(preg_replace('/[^0-9]/', '', $lastOrder)) + 1 : 1;
        $data['order_number'] = str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        return $data; // Devuelve los datos modificados
    }


}