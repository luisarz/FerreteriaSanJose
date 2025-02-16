<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CashBoxCorrelative extends Model
{
    use LogsActivity;
   protected $fillable = [
       'cash_box_id',
       'document_type_id',
       'serie',
       'start_number',
       'end_number',
       'current_number',
       'is_active',
   ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['cash_box_id', 'document_type_id', 'serie', 'start_number', 'end_number', 'current_number', 'is_active']);
    }
   public function cashBox()
   {
       return $this->belongsTo(CashBox::class);
   }
   public function document_type()
   {
         return $this->belongsTo(DocumentType::class);

   }
}
