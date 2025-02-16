<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'last_name',
        'email',
        'phone',
        'country_id',
        'departamento_id',
        'municipio_id',
        'distrito_id',
        'economicactivity_id',
        'wherehouse_id',
        'address',
        'nrc',
        'dui',
        'nit',
        'is_taxed',
        'is_active',
        'is_credit_client',
        'credit_limit',
        'credit_days',
        'credit_balance',
        'last_purched',
        'person_type_id',
        'document_type_id'

    ];
    public function country()
    {
        return $this->belongsTo(Country::class);
    }
    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }
    public function municipio()
    {
        return $this->belongsTo(Municipality::class);
    }
    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }
    public function economicactivity()
    {
        return $this->belongsTo(EconomicActivity::class);
    }
    public function wherehouse()
    {
        return $this->belongsTo(Branch::class);
    }
    public function sales()
    {
        return $this->hasMany(Order::class);
    }
    public function getFullnameAttribute()
    {
        return $this->name . ' ' . $this->last_name;

    }
    public function documenttypecustomer()
    {
        return $this->belongsTo(CustomerDocumentType::class, 'document_type_id');
    }
    public function persontype()
    {
        return $this->belongsTo(personType::class, 'person_type_id');
    }
}
