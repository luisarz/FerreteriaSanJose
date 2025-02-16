<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use softDeletes;

    protected $fillable = [
        'cashbox_open_id',
        'operation_date',
        'document_type_id',
        'document_internal_number',
        'wherehouse_id',
        'seller_id',
        'mechanic_id',
        'customer_id',
        'operation_condition_id',
        'payment_method_id',
        'sales_payment_status',
        'sale_status',
        'is_taxed',
        'have_retention',
        'net_amount',
        'taxe',
        'discount',
        'retention',
        'sale_total',
        'cash',
        'change',
        'casher_id',
        'billing_model',
        'transmision_type',
        'is_dte',
        'generationCode',
        'receiptStamp',
        'jsonUrl',
        'is_order',
        'is_order_closed_without_invoiced',
        'is_invoiced_order',
        'order_number',
        'discount_percentage',
        'discount_money',
        'total_order_after_discount',
    ];


    public function wherehouse(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function documenttype(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Employee::class);

    }
    public function mechanic(): BelongsTo
    {
        return $this->belongsTo(Employee::class);

    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
    public  function salescondition(): BelongsTo
    {
        return $this->belongsTo(OperationCondition::class, 'operation_condition_id');

    }
    public function paymentmethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id','id');
    }
    public function casher(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'casher_id');
    }
    public function saleDetails(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
    public function inventories(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function dteProcesado(): HasOne
    {
        return $this->hasOne(HistoryDte::class,'sales_invoice_id');
    }

    public function billingModel(): BelongsTo
    {
        return $this->belongsTo(BillingModel::class,'billing_model','id');

    }
    public function transmisionType(): BelongsTo
    {
        return $this->belongsTo(TransmisionType::class,'transmision_type','id');

    }
}