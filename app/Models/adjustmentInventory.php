<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class adjustmentInventory extends Model
{
    use softDeletes;

    protected $fillable = [
        'tipo',
        'branch_id',
        'fecha',
        'entidad',
        'user_id',
        'descripcion',
        'monto',
        'status'
    ];
    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');

    }
    public function employee(){
        return $this->belongsTo(Employee::class, 'user_id');
    }

    public function adjustItems(){
        return $this->hasMany(adjustmentInventoryItems::class, 'adjustment_id','');
    }
}
