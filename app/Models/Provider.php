<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Provider extends Model
{
    use HasFactory;
    use softDeletes;



    protected $fillable = [
        'legal_name',
        'comercial_name',
        'country_id',
        'department_id',
        'municipility_id',
        'distrito_id',
        'direction',
        'phone_one',
        'phone_two',
        'email',
        'nrc',
        'nit',
        'economic_activity_id',
        'condition_payment',
        'credit_days',
        'credit_limit',
        'balance',
        'provider_type',
        'is_active',
        'contact_seller',
        'phone_seller',
        'email_seller',
        'last_purchase',
        'purchase_decimals'
    ];
    public function pais()
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');

    }
    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'department_id', 'id');

    }

    public function distrito()
    {
        return $this->belongsTo(Municipality::class, 'distrito_id', 'id');
    }
    public function municipio()
    {
        return $this->belongsTo(Distrito::class, 'municipility_id', 'id');
    }
    public function economicactivity()
    {
        return $this->belongsTo(EconomicActivity::class, 'economic_activity_id', 'id');
    }


}
