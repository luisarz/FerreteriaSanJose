<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Distrito extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'name',
        'departamento_id',
    ];
    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }
    public function municipios()
    {
        return $this->hasMany(Municipality::class, 'distrito_id');
    }

}
