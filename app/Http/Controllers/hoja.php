<?php
//
//namespace App\Http\Controllers;
//
//use App\Models\Category;
//use App\Models\Customer;
//use App\Models\hoja1;
//use App\Models\Inventory;
//use App\Models\Marca;
//use App\Models\Price;
//use App\Models\Product;
//use App\Models\Provider;
//use Illuminate\Http\Request;
//use Illuminate\Support\Facades\Log;
//use Illuminate\Support\Facades\Storage;
//use Illuminate\Support\Facades\DB;
//
//class hoja extends Controller
//{
//    //
//    public function ejecutar()
//    {
//        set_time_limit(0);
//
//        //limpiar las tablas
//        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
////        Customer::truncate();
////
////        //customer
////        $clientes = DB::connection('sqlsrv')->table('Clientes')->get();
////        foreach ($clientes as $oldCliente) {
////            $cliente = new Customer();
//////            $cliente->id=$oldCliente->id_cliente;
////            $cliente->name=$oldCliente->Cliente;
////            $cliente->last_name=null;
////            $cliente->email="ferreteriasanjose.dte@gmail.com";
////            $cliente->phone='(503)'.$oldCliente->Telefono1;
////            $cliente->country_id=1;
////            $cliente->departamento_id=1;
////            $cliente->municipio_id=1;
////            $cliente->document_type_id=13;
////            $cliente->person_type_id=1;
////            $cliente->nrc=$oldCliente->Nrc;
////            $cliente->nit=$oldCliente->Nit;
////            $cliente->dui=null;
////            $cliente->is_taxed=true;
////            $cliente->wherehouse_id=3;
////            $cliente->save();
////
////        }
////       dd('Clientes');
////        Provider::truncate();
////
////        $providers=DB::connection('sqlsrv')->table('Proveedores')->get();
////        foreach ($providers as $provider){
////            $providerNew = new Provider();
////
//////            $providerNew->id = $provider->id_proveedor; // Asignando el ID manualmente si es necesario
////            $providerNew->legal_name = strtoupper($provider->Proveedor);
////            $providerNew->comercial_name =strtoupper($provider->Proveedor);
////            $providerNew->country_id = 1;
////            $providerNew->department_id =1;
////            $providerNew->municipility_id = 1;
////            $providerNew->distrito_id = 1;
////            $providerNew->direction = strtoupper($provider->Direccion);
////            $providerNew->phone_one = $provider->Telefono1;
////            $providerNew->phone_two = $provider->Telefono2;
////            $providerNew->email = null;
////            $providerNew->nrc = $provider->Nrc;
////            $providerNew->nit = $provider->Nit;
////            $providerNew->economic_activity_id = 1;
////            $providerNew->condition_payment =1;
////            $providerNew->credit_days =$provider->Plazo_credito;
////            $providerNew->credit_limit =$provider->Limite_credito;
////            $providerNew->balance =$provider->Balance;
////            $providerNew->provider_type = 1;
////            $providerNew->is_active = true;
////            $providerNew->contact_seller = strtoupper($provider->Contacto??'');
////            $providerNew->phone_seller = null;
////            $providerNew->email_seller = null;
////            $providerNew->last_purchase = null;
////            $providerNew->purchase_decimals = 2;
////            $providerNew->save();
////
////
////        }
////
////
////dd('Clinte y proveedores');
//
//
//        Product::truncate();
//        Marca::truncate();
//        Category::truncate();
//
//        Price::truncate();
//        Inventory::truncate();
////        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
//        $categorias = DB::connection('sqlsrv')->table('Lineas')->get();
//        foreach ($categorias as $category) {
//            $newCategory = new Category();
//            $newCategory->id = $category->Id;
//            $newCategory->name = trim($category->Nombre);
//            $newCategory->is_active = true;
//            $newCategory->save();
//        }
////
////        dd('Cater');
//        $brands = DB::connection('sqlsrv')->table('Marcas')->get();
//        foreach ($brands as $brand) {
//            $newBrand = new Marca();
//            $newBrand->id = $brand->Id;
//            $newBrand->nombre = trim($brand->Marca);
//            $newBrand->descripcion = trim($brand->Marca);
//            $newBrand->estado = true;
//            $newBrand->save();
//        }
////        dd('Marcas');
//        $products = DB::connection('sqlsrv')->table('Inventario')->get();
////        dd($products);
//        $lineasNuevas=0;
//        $marcasNuevas=0;
//        $productosNuevos=0;
//        $inventarioNuevo=0;
//        $productosNoCrados=[];
//        $inventariosNoCreados=[];
//        foreach ($products as $producto) {
//
//
//            try {
//                $nuevo = new Product();
//                $nuevo->id = $producto->Id;
//                $nuevo->name = trim($producto->Descripcion);
//                $nuevo->aplications = "";//str_replace(',', ';', $producto['Linea']);
//                $nuevo->sku = trim($producto->Codigo);
//                $nuevo->bar_code = trim($producto->Codigo_de_barra);
//                $nuevo->is_service = false;
//                //verifiamos si existe la linea
//                $idLinea = 0;
//                $categoria = Category::where('name',trim($producto->Linea))->first();
//                if (!$categoria) {
//                    $categoria = new Category();
//                    $categoria->name = $producto->Linea;
//                    $categoria->is_active = true;
//                    $categoria->save();
//                    $idLinea=$categoria->id;
//                    $lineasNuevas++;
//                }else{
//                    $idLinea=$categoria->id;
//                }
//                $nuevo->category_id =$idLinea;
////                revisamos si existe la marca
//                $idMarca = 0;
//                $marca = Marca::where('nombre', trim($producto->Marca))->first();
//                if (!$marca) {
//                    $marca = new Marca();
//                    $marca->nombre = $producto->Marca;
//                    $marca->descripcion = $producto->Marca;
//                    $marca->estado = true;
//                    $marca->save();
//                    $idMarca = $marca->id;
//                    $marcasNuevas++;
//                }else{
//                    $idMarca = $marca->id;
//                }
//
//                $nuevo->marca_id = $idMarca;
//                $nuevo->unit_measurement_id = 1;
//                $nuevo->is_taxed = true;
//                $nuevo->images =null;
//                $nuevo->is_active = true;
//                if($nuevo->save()){
//                    $productosNuevos++;
//                }else{
//                    $productosNoCrados[]= $producto->Id;
//                }
//
//
//                    //llenar el inventario
//                    $inventario = new Inventory();
////                    $inventario->id = $oldInventory->id_inventario;
//                    $inventario->product_id = $producto->Id;
//                    $inventario->branch_id = 3;
//                    $cost = $producto->Costo ?? 0; // Si $producto->cost es null, asigna 0
//                    $inventario->cost_without_taxes = $cost;
//                    $inventario->cost_with_taxes = $cost > 0 ? $cost * 1.13 : 0; // Evita multiplicar si es 0
//
////                    $stock = ($producto->unidades_presentacion * $oldInventory->saldo_caja) + $oldInventory->saldo_fraccion + $oldInventory->bonificables;
//                    $stock = $producto->Existencia ?? 0;
//
//                    $inventario->stock = $stock;
//                    $inventario->stock_min = $producto->E_minimo ?? 0;
//                    $inventario->stock_max = $producto->E_maximo ?? 0;
//                    $inventario->is_stock_alert = true;
//                    $inventario->is_expiration_date = false;
//                    $inventario->is_active = true;
//                   if( $inventario->save()){
//                        $inventarioNuevo++;
//                    }else{
//                        $inventariosNoCreados[]=$producto->Id;
//                   }
//                    //llenar los precios
//
//
//                        $precio = new Price();
//                        $precio->inventory_id = $inventario->id;
//                        $precio->name = 'Público';
//                        $precio->price = $producto->Precio_iva;
//                        $precio->utilidad=0;
//                        $precio->is_default = true;
//                        $precio->is_active = true;
//                        $precio->save();
//
//
//
//
//            } catch (\Exception $e) {
//                dd($e);
////                Log::error("Failed to save product ID {$producto['id']}: " . $e->getMessage());
//                dd($e->getMessage());
////                $items[] = $producto['id']; // Use the actual product ID for tracking failures
//            }
//        }
//        dd('productos' . $lineasNuevas . ' lineas nuevas y ' . $marcasNuevas . ' marcas nuevas'.
//            ' y ' . $productosNuevos . ' productos nuevos y ' . $inventarioNuevo . ' inventarios nuevos' .
//            ' productos no creados: '.implode(',',$productosNoCrados) .
//            ' inventarios no creados: '.implode(',',$inventariosNoCreados));
//
//
//
//    }
//}
