<?php

namespace App\Helpers;

use App\Models\Kardex;
use App\Models\Inventory;
use PhpParser\Node\Scalar\String_;

class KardexHelper
{
    public static function createKardexFromInventory(
        int    $branch_id,
        string $date,
        string $operation_type,
        string $operation_id,
        string $operation_detail_id,
        string $document_type,
        string $document_number,
        string $entity,
        string $nationality,
        int    $inventory_id,
        int    $previous_stock,
        int    $stock_in,
        int    $stock_out,
        int    $stock_actual,
        float  $money_in,
        float  $money_out,
        float  $money_actual,
        float  $sale_price,
        float  $purchase_price
    )
    {
        $kardex = Kardex::create([
            'branch_id' => $branch_id,
            'date' => $date,
            'operation_type' => $operation_type,
            'operation_id' => $operation_id,
            'operation_detail_id' => $operation_detail_id,
            'document_type' => $document_type,
            'document_number' => $document_number,
            'entity' => $entity,
            'nationality' => $nationality,
            'inventory_id' => $inventory_id,
            'previous_stock' => $previous_stock,
            'stock_in' => $stock_in,
            'stock_out' => $stock_out,
            'stock_actual' => $stock_actual,
            'money_in' => $money_in,
            'money_out' => $money_out,
            'money_actual' => $money_actual,
            'sale_price' => $sale_price,
            'purchase_price' => $purchase_price,
        ]);

        return (bool) $kardex;
    }


}
