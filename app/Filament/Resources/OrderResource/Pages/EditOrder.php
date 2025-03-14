<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Helpers\KardexHelper;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\SaleItem;
use EightyNine\FilamentPageAlerts\PageAlert;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('Volver'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Enviar Orden')
                ->color('success')
                ->icon('heroicon-o-check')
                ->action('save')
                ->extraAttributes([
                    'class' => 'alig', // Tailwind para ajustar el margen alinearlo a la derecha

                ]),

            Action::make('cancelSale')
                ->label('Eliminar Orden')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Confirmación!!')
                ->modalSubheading('¿Estás seguro de que deseas cancelar esta venta? Esta acción no se puede deshacer.')
                ->modalButton('Sí, cancelar venta')
                ->action(function (Actions\DeleteAction $delete) {
//
                    $this->record->delete();
                    SaleItem::where('sale_id', $this->record->id)->delete();
                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }

    #[On('refreshSale')]
    public function refresh(): void
    {
    }

    protected function afterSave(): void
    {
        Notification::make('Orden enviada')
            ->title('Orden enviada')
            ->body('La orden ha sido enviada correctamente')
            ->success()
            ->send();

        PageAlert::make()
            ->title('Error al anular venta')
            ->body('No se puede cancelar una venta con DTE')
            ->danger()
            ->send();
        $this->redirect(static::getResource()::getUrl('index'));

    }


}
