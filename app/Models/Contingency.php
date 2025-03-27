<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Contingency extends Model
{

    protected $fillable=[
        'warehouse_id',
        'uuid_hacienda',
        'start_date',
        'end_date',
        'contingency_types_id',
        'continvengy_motivation',
        'end_date'
    ];
    protected $casts = [
        'end_date' => 'datetime',
    ];

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Branch::class,'warehouse_id','id');
    }
    public function contingencyType(): BelongsTo
    {
        return $this->belongsTo(ContingencyType::class,'contingency_types_id','id');
    }

}
