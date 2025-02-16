<?php

namespace App\Http\Controllers;

use App\Models\Category;
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

        Price::truncate();
        Inventory::truncate();
        Product::truncate();
        Marca::truncate();
        Category::truncate();

        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        $fileName = "/DTEs/products.json";
        if (Storage::disk('public')->exists($fileName)) {
            $fileContent = Storage::disk('public')->get($fileName);
            $jsonFile = json_decode($fileContent, true); // Decodificar JSON en un array asociativo
        }
        $products = $jsonFile['products'];
        $brands = $jsonFile['brands'];
        $categories = $jsonFile['categories'];

        foreach ($brands as $brand) {
            $marca = new Marca();
            $marca->id = $brand['id'];
            $marca->descripcion = $brand['name'];
            $marca->nombre = $brand['name'];
            $marca->estado = true;
            $marca->save();
        }

        foreach ($categories as $category) {
            $categoria = new Category();
            $categoria->id = $category['id'];
            $categoria->name = $category['name'];
            $categoria->is_active = true;
            $categoria->commission_percentage = 0;
            $categoria->save();
        }


        $items = [];
        $products = hoja1::all();
        foreach ($products as $producto) {
            try {
                $nuevo = new Product();
                $nuevo->id = $producto->id;
                $nuevo->name = trim($producto->Produ);
                $nuevo->aplications = str_replace(',', ';', $producto['Linea']);
                $nuevo->sku = trim($producto['sku']);
                $nuevo->bar_code = $producto['sku'];
                $nuevo->is_service = false;
                $nuevo->category_id =98;
                $nuevo->marca_id = Marca::where('nombre', $producto->marca)->first()->id ?? 1;
                $nuevo->unit_measurement_id = 1;
                $nuevo->is_taxed = true;
                $nuevo->images = null;
                $nuevo->is_active = true;
                $nuevo->save();

                //llenar el inventario
                $inventario = new Inventory();
                $inventario->product_id = $producto->id;
                $inventario->branch_id = 3;
                $cost = $producto->cost ?? 0; // Si $producto->cost es null, asigna 0
                $inventario->cost_without_taxes = $cost;
                $inventario->cost_with_taxes = $cost > 0 ? $cost * 1.13 : 0; // Evita multiplicar si es 0

                $inventario->stock = $producto->Existencia>0 ? $producto->Existencia : 0;
                $inventario->stock_min = $producto['ExisteMinima'] ?? 0;
                $inventario->stock_max = 0;
                $inventario->is_stock_alert = true;
                $inventario->is_expiration_date = false;
                $inventario->is_active = true;
                $inventario->save();
                //llenar los precios
                $precio = new Price();
                $precio->inventory_id = $inventario->id;
                $precio->name = "Público";
                $precio->price = isset($producto->Precio) && is_numeric($producto->Precio) ? $producto->Precio : 0;

                $precio->is_default = true;
                $precio->is_active = true;
                $precio->save();

            } catch (\Exception $e) {
                $items[] = $producto['id']; // Use the actual product ID for tracking failures
                Log::error("Failed to save product ID {$producto['id']}: " . $e->getMessage());
            }
        }

// Output any failed product IDs
        if (!empty($items)) {
            dd($items); // Output failed IDs after processing all products
        }


    }
}
