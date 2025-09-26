<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CashBoxOpen extends Model
{
//    use LogsActivity;

//    protected $fillable = [
//          'cashbox_id',
//          'open_employee_id',
//          'opened_at',
//          'open_amount',
//          'saled_amount',
//          'ordered_amount',
//          'out_cash_amount',
//          'in_cash_amount',
//          'closed_amount',
//          'closed_at',
//          'close_employee_id',
//          'status',
//      ];

    protected $fillable = [

        'cashbox_id',
        'open_employee_id',
        'opened_at',
        'open_amount',
        'ingreso_factura',
        'ingreso_ccf',
        'ingreso_ordenes',
        'ingreso_taller',
        'ingreso_caja_chica',
        'ingreso_totales',
        'egreso_caja_chica',
        'egreso_nc',
        'egresos_totales',
        'saldo_efectivo_ventas',
        'saldo_tarjeta',
        'saldo_cheque',
        'saldo_efectivo_ordenes',
        'saldo_caja_chica',
        'saldo_egresos_totales',
        'saldo_total_operaciones',
        'closed_at',
        'close_employee_id',
        'status'
    ];

//    public function getActivitylogOptions(): LogOptions
//    {
//        return LogOptions::defaults()
//            ->logOnly(['cashbox_id', 'open_employee_id', 'opened_at', 'open_amount', 'saled_amount', 'ordered_amount', 'out_cash_amount', 'in_cash_amount', 'closed_amount', 'closed_at', 'close_employee_id', 'status']);
//    }

//    public function getActivitylogOptions(): LogOptions
//    {
//        return LogOptions::defaults()
//            ->logOnly([
//                'cashbox_id',
//                'open_employee_id',
//                'opened_at',
//                'open_amount',
//                'ingreso_factura',
//                'ingreso_ccf',
//                'ingreso_ordenes',
//                'ingreso_caja_chica',
//                'ingreso_totales',
//                'egreso_caja_chica',
//                'egreso_nc',
//                'egresos_totales',
//                'saldo_efectivo_ventas',
//                'saldo_tarjeta',
//                'saldo_cheque',
//                'saldo_efectivo_ordenes',
//                'saldo_caja_chica',
//                'saldo_egresos_totales',
//                'saldo_total_operaciones',
//                'closed_at',
//                'close_employee_id',
//                'status'
//            ]);
//    }

    public function cashbox(): BelongsTo
    {
        return $this->belongsTo(CashBox::class);
    }

    public function openEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'open_employee_id');
    }

    public function closeEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'close_employee_id');
    }


}
