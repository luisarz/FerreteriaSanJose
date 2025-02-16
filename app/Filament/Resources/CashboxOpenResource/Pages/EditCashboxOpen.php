<?php

namespace App\Filament\Resources\CashboxOpenResource\Pages;

use App\Filament\Resources\CashboxOpenResource;
use App\Models\CashBox;
use App\Service\GetCashBoxOpenedService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class EditCashboxOpen extends EditRecord
{
    protected static string $resource = CashboxOpenResource::class;

    public function getTitle(): string|Htmlable
    {
        return 'Cerrar Caja';
    }

    public function afterSave(): void
    {
        $record = $this->record->id;

        $cashboxOpen = CashboxOpenResource::getModel()::find($record);

        $totalIngresos = (new GetCashBoxOpenedService())->minimalCashBoxTotal('Ingreso');
        $totalEgresos = (new GetCashBoxOpenedService())->minimalCashBoxTotal('Egreso');
        $totalSale = (new GetCashBoxOpenedService())->getTotal(false);
        $totalOrder = (new GetCashBoxOpenedService())->getTotal(true, true);
        $montoApertura = $cashboxOpen->open_amount;
        $totalClose = ($montoApertura + $totalIngresos + $totalOrder + $totalSale) - $totalEgresos;

        $cashboxOpen->closed_at = now();
//        $cashboxOpen->close_employee_id = auth()->user()->employee->id;
        $cashboxOpen->status = 'closed';
        $cashboxOpen->saled_amount = $totalSale;
        $cashboxOpen->ordered_amount = $totalOrder;
        $cashboxOpen->out_cash_amount = $totalEgresos;
        $cashboxOpen->in_cash_amount = $totalIngresos;
        $cashboxOpen->closed_amount = $totalClose;
        $cashboxOpen->save();

        $cashbox = CashBox::find($cashboxOpen->cashbox_id);
        $cashbox->is_open = 0;
        $cashbox->balance=0;
        $cashbox->save();
        $this->redirect(static::getResource()::getUrl('index'));

    }
}
