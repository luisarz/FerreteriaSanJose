<?php

namespace App\Filament\Resources\Quotes\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Helpers\KardexHelper;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\SaleItem;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Livewire\Attributes\On;

class EditQuote extends EditRecord
{
    protected static string $resource = QuoteResource::class;

    public function getTitle(): string
    {
        return '';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Volver'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Generar Cotización')
                ->color('success')
                ->icon('heroicon-o-check')
                ->action('save')
                ->extraAttributes([
                    'class' => 'alig', // Tailwind para ajustar el margen alinearlo a la derecha

                ]),

            Action::make('cancelSale')
                ->label('Eliminar Cotización')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Confirmación!!')
                ->modalSubheading('¿Estás seguro de que deseas cancelar esta venta? Esta acción no se puede deshacer.')
                ->modalButton('Sí, cancelar venta')
                ->action(function (DeleteAction $delete) {
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
        Notification::make('Cotización Creada')
            ->title('Cotizacion Creada')
            ->body('La cotizacion ha sido enviada correctamente')
            ->success()
            ->send();
        $this->redirect(static::getResource()::getUrl('index'));

    }


}
