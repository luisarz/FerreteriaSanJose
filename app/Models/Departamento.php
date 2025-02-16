<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Sushi\Sushi;

class Departamento extends Model
{
//    use sushi;
    use HasFactory;
    protected $table = 'departamentos';
    protected $primaryKey = 'id';
    protected $fillable = ['code', 'name', 'is_active'];
    public function distritos()
    {
        return $this->hasMany(Distrito::class);
    }

//    public function getRows(): array
//    {
//        //API
//        $departments = Http::get('http://api-fel-sv-dev.olintech.com/api/Catalog/cities')->json();
//        //filtering some attributes
//        return Arr::map($departments, function ($item) {
//            return Arr::only($item,
//                [
//                    'code',
//                    'name',
//                ]
//            );
//        });
//    }

}
