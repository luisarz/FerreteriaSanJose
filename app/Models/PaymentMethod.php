<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    //
    use softDeletes;
    protected $fillable=[
        'code',
        'name',
        'is_active'

    ];

}
