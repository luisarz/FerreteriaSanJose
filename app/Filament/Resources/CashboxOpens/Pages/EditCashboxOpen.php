<?php

namespace App\Filament\Resources\CashboxOpens\Pages;

use App\Filament\Resources\CashboxOpens\CashboxOpenResource;
use App\Models\CashBox;
use App\Service\GetCashBoxOpenedService;
use App\Services\CashBoxResumenService;
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
        $resumen = new CashBoxResumenService();

        $cashboxOpen = CashboxOpenResource::getModel()::find($record);


        $montoApertura = $cashboxOpen->open_amount;
//        $cashboxOpen->close_employee_id = auth()->user()->employee->id;
        $cashboxOpen->status = 'closed';
        $cashboxOpen->ingreso_factura = $resumen->ingreso_factura;
        $cashboxOpen->ingreso_ccf = $resumen->ingreso_ccf;
        $cashboxOpen->ingreso_ordenes = $resumen->ingreso_ordenes;
        $cashboxOpen->ingreso_taller = $resumen->ingreso_taller;
        $cashboxOpen->ingreso_caja_chica = $resumen->ingreso_caja_chica;
        $cashboxOpen->ingreso_totales = $resumen->ingreso_total;
        $cashboxOpen->egreso_caja_chica = $resumen->egreso_caja_chica;
        $cashboxOpen->egreso_nc = $resumen->egreso_nc;
        $cashboxOpen->egresos_totales = $resumen->egreso_total;
        $cashboxOpen->saldo_efectivo_ventas = $resumen->saldo_efectivo_ventas;
        $cashboxOpen->saldo_tarjeta = $resumen->saldo_tarjeta;
        $cashboxOpen->saldo_cheque = $resumen->saldo_cheques;
        $cashboxOpen->saldo_efectivo_ordenes = $resumen->saldo_efectivo_ordenes;
        $cashboxOpen->saldo_caja_chica = $resumen->saldo_caja_chica;
        $cashboxOpen->saldo_egresos_totales = $resumen->egreso_total;
        $cashboxOpen->saldo_total_operaciones = $resumen->saldo_total + $montoApertura;
        $cashboxOpen->closed_at = now();



        $cashboxOpen->save();

        $cashbox = CashBox::find($cashboxOpen->cashbox_id);
        $cashbox->is_open = 0;
        $cashbox->balance=0;
        $cashbox->save();
        $this->redirect(static::getResource()::getUrl('index'));

    }
}
