<?php

namespace App\Helpers;

use App\Models\InventoryCostoHistory;
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
        float  $purchase_price,
    )
    {
        $promedial_cost = 0;

        if ($stock_in > 0) {
            $inventory = Inventory::find($inventory_id);
            // Obtenemos el penúltimo registro
            $penultimoRegistro = InventoryCostoHistory::orderByDesc('id')->skip(1)->first();
            // Si no hay penúltimo registro, usamos 0 como costo anterior
            $costo_anterior = $penultimoRegistro->costo_actual ?? 0;
            $stockAnterior = $inventory->stock - $stock_in; // Corrige 'soctk' si era typo
            // Evitar división por cero
            $totalCantidad = $stockAnterior + $stock_in;
            if ($totalCantidad > 0) {
                $promedial_cost = (($stockAnterior * $costo_anterior) + ($stock_in * $purchase_price)) / $totalCantidad;
            }
        }




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
            'promedial_cost' => $promedial_cost
        ]);

        return (bool)$kardex;
    }


}
