<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DteTransmisionWherehouse extends Model
{
    protected $fillable = ['wherehouse', 'billing_model', 'transmision_type', 'printer_type'];

    public function where_house(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'wherehouse', 'id');
    }
    public function billingModel(): BelongsTo
    {
        return $this->belongsTo(BillingModel::class,'billing_model', 'id');
    }
    public function transmisionType(): BelongsTo
    {
        return $this->belongsTo(TransmisionType::class,'transmision_type', 'id');
    }
}
