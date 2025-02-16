<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoryDte extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'sales_invoice_id',
        'version',
        'ambiente',
        'versionApp',
        'estado',
        'codigoGeneracion',
        'selloRecibido',
        'fhProcesamiento',
        'clasificaMsg',
        'codigoMsg',
        'descripcionMsg',
        'observaciones',
        'dte',
    ];
    protected $casts = [
        'dte' => 'array',
    ];
    public function salesInvoice()
    {
        return $this->belongsTo(Sale::class,'sales_invoice_id','id');
    }
}
