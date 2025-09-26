<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Municipality extends Model
{
    use HasFactory;
    use softDeletes;
    protected $fillable = ['code', 'name', 'distrito_id', 'is_active'];



    public function distrito(): BelongsTo
    {
        return $this->belongsTo(Distrito::class);
    }
}
