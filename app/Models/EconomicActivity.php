<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EconomicActivity extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'description'];
    public function companies(): HasMany
    {
        return $this->hasMany(Company::class, 'economic_activity_id', 'id');
    }
    public function getDescriptionWithCodeAttribute(): string
    {
        return "{$this->description} ({$this->code})";
    }

}
