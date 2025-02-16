<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name', 'aplications', 'sku', 'bar_code', 'is_service', 'category_id',
        'marca_id', 'unit_measurement_id', 'is_taxed', 'images', 'is_active'
    ];

    protected $casts = [
//        'tribute_id' => 'array', // Casts tribute_id as an array
        'images' => 'array',     // Casts images as an array
    ];

//    public function tributes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
//    {
//        return $this->belongsToMany(Tribute::class, 'product_tributes', 'product_id', 'tribute_id');
//    }
    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }
    public function marca(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Marca::class, 'marca_id', 'id');
    }
    public function unitmeasurement(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(UnitMeasurement::class, 'unit_measurement_id', 'id');
    }
    public function inventories(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Inventory::class);
    }



}
