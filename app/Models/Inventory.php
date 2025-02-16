<?php

namespace App\Models;

use App\Helpers\KardexHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'branch_id',
        'stock',
        'stock_min',
        'stock_max',
        'cost_without_taxes',
        'cost_with_taxes',
        'is_stock_alert',
        'is_expiration_date',
        'is_active',
    ];

    protected static function booted()
    {
        parent::booted();
        static::created(function ($inventory) {
            $kardex = KardexHelper::createKardexFromInventory(
                $inventory->branch_id, // Se pasa solo el valor de branch_id (entero)
                now(), // Fecha
                'INVENTARIO INICIAL', // Tipo de operación
                0, // operation_id
                0, // operation_detail_id
                0, // document_type
                0, // document_number
                'INVENTARIO INICIAL', // entity
                'SALVADOREÑO', // nationality
                $inventory->id, // inventory_id
                0, // previous_stock
                $inventory->stock, // stock_in
                0, // stock_out
                $inventory->stock, // stock_actual
                $inventory->stock * $inventory->cost_without_taxes, // money_in
                0, // money_out
                $inventory->stock * $inventory->cost_without_taxes, // money_actual
                0, // sale_price
                $inventory->cost_without_taxes // purchase_price
            );

            // Verifica si la creación del Kardex fue exitosa
            if (!$kardex) {
                \Log::error("Error al crear Kardex para el item de compra: {$item->id}");
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }

}
