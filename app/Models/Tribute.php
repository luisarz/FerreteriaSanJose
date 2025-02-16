<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tribute extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'name', 'is_percentage', 'rate', 'is_active'];
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tributes', 'tribute_id', 'product_id');
    }
}
