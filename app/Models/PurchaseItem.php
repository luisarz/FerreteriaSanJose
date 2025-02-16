<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{

    protected $fillable = [
        'purchase_id',
        'inventory_id',
        'quantity',
        'price',
        'discount',
        'total',
    ];
    public function pruchase()
    {
        return $this->belongsTo(Purchase::class);

    }
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
}
