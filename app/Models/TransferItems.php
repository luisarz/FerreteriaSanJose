<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItems extends Model
{
    protected $fillable = [
        'transfer_id',
        'inventory_id',
        'quantity',
        'price',
        'total',
        'status_sent',
        'status_recived',
    ];

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class, 'transfer_id', 'id');
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class, 'inventory_id', 'id');
    }
}
