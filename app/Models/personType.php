<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class personType extends Model
{
   protected $fillable = ['code', 'name', 'is_active'];
}
