<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kardex extends Model
{

    use softDeletes;
    protected $table = 'kardex';
    protected $fillable = [
        'branch_id',
        'date',
        'operation_type',
        'operation_id',
        'operation_detail_id',
        'document_type',
        'document_number',
        'entity',
        'nationality',
        'inventory_id',
        'previous_stock',
        'stock_in',
        'stock_out',
        'stock_actual',
        'money_in',
        'money_out',
        'money_actual',
        'sale_price',
        'purchase_price',
        'promedial_cost'
    ];

    public function whereHouse()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }
}
