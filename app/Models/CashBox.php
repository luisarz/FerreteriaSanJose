<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CashBox extends Model
{
//    use LogsActivity;
    protected $fillable = [
        'branch_id',
        'description',
        'balance',
        'is_active',
        'is_open',
    ];
//    public function getActivitylogOptions(): LogOptions
//    {
//        return LogOptions::defaults()
//            ->logOnly(['branch_id', 'description', 'balance', 'is_active', 'is_open']);
//    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);

    }
    public function correlatives()
    {
        return $this->hasMany(CashBoxCorrelative::class);
    }
}
