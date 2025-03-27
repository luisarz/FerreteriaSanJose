<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HistoryDte extends Model
{
    use SoftDeletes;

    /**
     * @var mixed|null
     */
    protected $fillable = [
        'sales_invoice_id',
        'version',
        'ambiente',
        'versionApp',
        'estado',
        'codigoGeneracion',
        'selloRecibido',
        'num_control',
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
