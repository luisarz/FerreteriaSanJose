<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmallCashBoxOperation extends Model
{
    use softDeletes;


    protected $fillable=[
        'cash_box_open_id',
        'employ_id',
        'operation',
        'amount',
        'concept',
        'voucher',
        'status',
    ];
    protected $casts = [
        'voucher' => 'array',
    ];
    public function cashBoxOpen(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CashBoxOpen::class,'cash_box_open_id');
    }
    public function employee(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Employee::class,'employ_id');
    }
}
