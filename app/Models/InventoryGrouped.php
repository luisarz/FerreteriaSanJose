<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryGrouped extends Model
{
    protected $fillable = [
        'inventory_grouped_id',
        'inventory_child_id',
        'quantity',
        'is_active'
    ];

    public function inventoryGrouped(): BelongsTo
    {
        return $this->belongsTo(Inventory::class,'inventory_grouped_id','id');
    }
    public function inventoryChild(): BelongsTo
    {
        return $this->belongsTo(Inventory::class,'inventory_child_id','id');
    }
}
