<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RetentionTaxe extends Model
{
    protected $fillable = ['code', 'name', 'is_percentage', 'rate', 'is_active'];
}
