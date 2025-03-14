<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'price',
        'utilidad',
        'is_default',
        'is_active',
    ];
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }
    public static function boot()
    {
        parent::boot();

        static::saving(function ($precio) {
            if ($precio->is_default) {
                static::where('inventory_id', $precio->inventory_id)
                    ->where('id', '!=', $precio->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
