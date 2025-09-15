<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Marca extends Model
{
    use HasFactory;
    protected $fillable = ['nombre', 'descripcion', 'imagen', 'estado'];

    protected $casts = [
        'imagen' => 'array',
    ];
    public function productos(): HasMany
    {
        return $this->hasMany(Product::class, 'marca_id', 'id');

    }
}
