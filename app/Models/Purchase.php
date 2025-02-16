<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Provider;


class Purchase extends Model
{
    use softDeletes;

    protected $fillable = [
        'provider_id',
        'employee_id',
        'wherehouse_id',
        'purchase_date',
        'document_type',
        'document_number',
        'pruchase_condition',
        'credit_days',
        'status',
        'have_perception',
        'net_value',
        'taxe_value',
        'perception_value',
        'purchase_total',
        'paid'
    ];

    public function provider()
    {
        return $this->belongsTo(\App\Models\Provider::class);
    }

    public function employee()
    {
        return $this->belongsTo(\App\Models\Employee::class);
    }


    public function wherehouse(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);

    }
}
