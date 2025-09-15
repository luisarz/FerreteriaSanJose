<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class adjustmentInventoryItems extends Model
{
   protected $fillable = [
        'adjustment_id',
       'inventory_id',
       'cantidad',
       'precio_unitario',
       'total',
       'description'
   ];
    public function Adjutment()
    {
        return $this->belongsTo(adjustmentInventory::class);
    }
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
    public function whereHouse()
    {
        return $this->belongsTo(Branch::class, 'wherehouse_id','id');

    }
}
