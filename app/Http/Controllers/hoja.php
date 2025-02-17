<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\hoja1;
use App\Models\Inventory;
use App\Models\Marca;
use App\Models\Price;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class hoja extends Controller
{
    //
    public function ejecutar()
    {
        set_time_limit(0);

        //limpiar las tablas
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        //customer
        $clientes = DB::connection('mariadb2')->table('cliente')->get();
        foreach ($clientes as $oldCliente) {
            $cliente = new Customer();

        }







        Price::truncate();
        Inventory::truncate();
        Product::truncate();
        Marca::truncate();
        Category::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        $categorias = DB::connection('mariadb2')->table('categoria')->get();
        foreach ($categorias as $category) {
            $newCategory = new Category();
            $newCategory->id = $category->id_categoria;
            $newCategory->name = $category->nombre_categoria;
            $newCategory->is_active = true;
            $newCategory->save();
        }
        $brands = DB::connection('mariadb2')->table('marca')->get();
        foreach ($brands as $brand) {
            $newBrand = new Marca();
            $newBrand->id = $brand->id_marca;
            $newBrand->nombre = $brand->nombre_marca;
            $newBrand->descripcion = $brand->nombre_marca;
            $newBrand->estado = true;
            $newBrand->save();
        }
        $products = DB::connection('mariadb2')->table('producto')->get();
        foreach ($products as $producto) {
            try {
                $nuevo = new Product();
                $nuevo->id = $producto->id_producto;
                $nuevo->name = trim($producto->producto);
                $nuevo->aplications = "";//str_replace(',', ';', $producto['Linea']);
                $nuevo->sku = trim($producto->codigo_barra);
                $nuevo->bar_code = trim($producto->codigo_barra);
                $nuevo->is_service = false;
                $nuevo->category_id = $producto->categoria;
                $nuevo->marca_id = ($producto->marca == 274) ? 1 : $producto->marca;
                $nuevo->unit_measurement_id = 1;
                $nuevo->is_taxed = true;
                $nuevo->images = null;
                $nuevo->is_active = true;
                $nuevo->save();


                $inventories = DB::connection('mariadb2')
                    ->table('inventario')
                    ->where('id_producto', $producto->id_producto) // Filtra por product_id
                    ->get();
//
                foreach ($inventories as $oldInventory) {
                    //llenar el inventario
                    $inventario = new Inventory();
                    $inventario->id = $oldInventory->id_inventario;
                    $inventario->product_id = $oldInventory->id_producto;
                    $inventario->branch_id = $oldInventory->id_sucursal;
                    $cost = $oldInventory->costo_compra ?? 0; // Si $producto->cost es null, asigna 0
                    $inventario->cost_without_taxes = $cost;
                    $inventario->cost_with_taxes = $cost > 0 ? $cost * 1.13 : 0; // Evita multiplicar si es 0

                    $stock = ($producto->unidades_presentacion * $oldInventory->saldo_caja) + $oldInventory->saldo_fraccion + $oldInventory->bonificables;

                    $inventario->stock = $stock;
                    $inventario->stock_min = $oldInventory->stock_minimo ?? 0;
                    $inventario->stock_max = $oldInventory->stock_minimo ?? 0;
                    $inventario->is_stock_alert = true;
                    $inventario->is_expiration_date = false;
                    $inventario->is_active = true;
                    $inventario->save();
                    //llenar los precios

                    $precios = DB::connection('mariadb2')
                        ->table('precio')
                        ->where('id_inventario', $oldInventory->id_inventario) // Filtra por product_id
                        ->get();
                    foreach ($precios as $price){
                        $precio = new Price();
                        $precio->inventory_id = $price->id_inventario;
                        $precio->name = $price->descripcion;
                        $precio->price = $price->precio;

                        $precio->is_default = ($price->mostrar == 1) ? true : false;
                        $precio->is_active = true;
                        $precio->save();
                    }


                }


            } catch (\Exception $e) {
                dd($e);
//                Log::error("Failed to save product ID {$producto['id']}: " . $e->getMessage());
//                dd($e->getMessage());
//                $items[] = $producto['id']; // Use the actual product ID for tracking failures
            }
        }


    }
}
