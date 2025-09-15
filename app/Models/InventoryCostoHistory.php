<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryCostoHistory extends Model
{
    protected $fillable = [
        'inventory_id',
        'inventory_id',
        'costo_anterio',
        'costo_actual',
        'fecha'
    ];

}
