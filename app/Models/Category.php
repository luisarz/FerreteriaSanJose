<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Category extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = ['name', 'parent_id', 'is_active','commission_percentage'];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'parent_id']);
    }
    public function category()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }
}
