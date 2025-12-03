<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * Eager load wherehouse relation by default
     */
    protected $with = ['wherehouse'];

    protected $fillable = [
        'name',
        'lastname',
        'email',
        'phone',
        'address',
        'photo',
        'birthdate',
        'gender',
        'marital_status',
        'marital_name',
        'marital_phone',
        'dui',
        'nit',
        'department_id',
        'municipalitie_id',
        'distrito_id',
        'branch_id',
        'job_title_id',
        'is_comisioned',
        'comision',
        'is_active'];
    protected $casts = [
        'photo' => 'array',
    ];
    public function getFullNameAttribute()
    {
        return $this->name . ' ' . $this->lastname;
    }
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'department_id');
    }
    public function municipio()
    {
        return $this->belongsTo(Municipality::class, 'municipalitie_id');

    }
    public function distrito()
    {
        return $this->belongsTo(Distrito::class, 'distrito_id');
    }
    public function wherehouse()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
    public function job()
    {
        return $this->belongsTo(JobTitle::class, 'job_title_id');
    }
}
