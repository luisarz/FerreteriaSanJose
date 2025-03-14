<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EmployeesController extends Controller
{
    public function sales($id_employee, $star_date, $end_date)
    {
        $startDate = Carbon::createFromFormat('d-m-Y', $star_date)->startOfDay();
        $endDate = Carbon::createFromFormat('d-m-Y', $end_date)->endOfDay();


        $categories = Category::whereNull('parent_id')
            ->select('id', 'name', 'commission_percentage')
            ->orderBy('name', 'asc') // Ordenar categorías alfabéticamente
            ->get();

        $sales = Sale::with([
            'saleDetails' => function ($query) {
                $query->select('sale_id', 'inventory_id', 'quantity', 'price', 'total');
            },
            'saleDetails.inventory' => function ($query) {
                $query->select('id', 'product_id');
            },
            'saleDetails.inventory.product' => function ($query) {
                $query->select('id', 'name', 'category_id');
            },
//            'saleDetails.inventory.product.category' => function ($query) {
//                $query->select('id', 'name', 'parent_id');
//            },
            'saleDetails.inventory.product.category.parent' => function ($query) {
                $query->select('id', 'name', 'commission_percentage');
            }
        ])
            ->whereBetween('operation_date', [$startDate, $endDate])
            ->where('seller_id', $id_employee)
            ->select('id', 'document_internal_number', 'operation_date', 'sale_total', 'sale_status')
            ->get();

        $empleado = Employee::where('id', $id_employee)->select('name', 'lastname', 'phone', 'gender', 'dui', 'nit')->get();
// Crear el encabezado con las categorías y sus porcentajes de comisión
        $vendedor= $empleado[0]->name.' '.$empleado[0]->lastname.' - DUI '.$empleado[0]->dui??'';
        $header = [
            "empleado" => $empleado,
            'StartDate' => $startDate,
            'EndDate' => $endDate,
            'categorias' => []
        ];

        foreach ($categories as $category) {
            $header['categorias'][$category->name] = $category->commission_percentage . '%';
        }
        $header['categorias']['Sin Categoría'] = '0%'; // Agregar "Sin Categoría" con 0% de comisión

        $report = [$header]; // Incluir el encabezado en el reporte

// Inicializar un arreglo para acumular los totales por categoría
        $totalCategories = [];
        $totalCommissions = []; // Arreglo para acumular las comisiones por categoría

        foreach ($categories as $category) {
            $totalCategories[$category->name] = 0;
            $totalCommissions[$category->name] = 0; // Inicializar las comisiones en 0
        }
        $totalCategories['Sin Categoría'] = 0;
        $totalCommissions['Sin Categoría'] = 0; // Inicializar las comisiones para "Sin Categoría"

// Crear un arreglo para las ventas por fecha
        $ventasPorFecha = [];

        foreach ($sales as $sale) {
            $date = $sale->operation_date;

            if (!isset($ventasPorFecha[$date])) {
                $ventasPorFecha[$date] = [
                    'date' => $date,
                    'categories' => [],
                ];

                // Inicializar todas las categorías principales con 0 y agregar el porcentaje de comisión
                foreach ($categories as $category) {
                    $ventasPorFecha[$date]['categories'][$category->name] = [
                        'ventas' => 0,
                        'comision_porcentaje' => $category->commission_percentage, // Agregar el porcentaje de comisión
                        'comision_total' => 0 // Inicializar la comisión total en 0
                    ];
                }
                // Asegurarse de que la categoría "Sin Categoría" esté presente
                $ventasPorFecha[$date]['categories']['Sin Categoría'] = [
                    'ventas' => 0,
                    'comision_porcentaje' => 0, // No hay comisión para "Sin Categoría"
                    'comision_total' => 0
                ];
            }

            // Sumar las ventas por categoría para esta fecha
            foreach ($sale->saleDetails as $detail) {
                // Verificar si el inventario existe
                if (!$detail->inventory) {
                    // Si no hay inventario, sumar a "Sin Categoría"
                    $ventasPorFecha[$date]['categories']['Sin Categoría']['ventas'] += $detail->total;
                    $totalCategories['Sin Categoría'] += $detail->total;
                    continue; // Saltar al siguiente detalle
                }

                // Verificar si el producto existe
                if (!$detail->inventory->product) {
                    // Si no hay producto, sumar a "Sin Categoría"
                    $ventasPorFecha[$date]['categories']['Sin Categoría']['ventas'] += $detail->total;
                    $totalCategories['Sin Categoría'] += $detail->total;
                    continue; // Saltar al siguiente detalle
                }

                // Verificar si la categoría existe
                $category = $detail->inventory->product->category;
                if (!$category) {
                    // Si no hay categoría, sumar a "Sin Categoría"
                    $ventasPorFecha[$date]['categories']['Sin Categoría']['ventas'] += $detail->total;
                    $totalCategories['Sin Categoría'] += $detail->total;
                    continue; // Saltar al siguiente detalle
                }

                // Verificar si la categoría tiene una categoría padre válida
                if ($category->parent) {
                    $categoryName = $category->parent->name; // Usar la categoría padre
                    $commissionPercentage = $category->parent->commission_percentage; // Obtener el porcentaje de comisión
                } else {
                    $categoryName = 'Sin Categoría'; // Si no tiene categoría padre, asignar a "Sin Categoría"
                    $commissionPercentage = 0; // No hay comisión para "Sin Categoría"
                }

                // Si la categoría existe en el reporte, sumar las ventas y calcular la comisión
                if (isset($ventasPorFecha[$date]['categories'][$categoryName])) {
                    $ventasPorFecha[$date]['categories'][$categoryName]['ventas'] += $detail->total;
                    $totalCategories[$categoryName] += $detail->total;

                    // Calcular la comisión y sumarla al total de comisiones
                    $commission = $detail->total * ($commissionPercentage / 100);
                    $ventasPorFecha[$date]['categories'][$categoryName]['comision_total'] += $commission;
                    $totalCommissions[$categoryName] += $commission;
                } else {
                    // Si la categoría no existe en el reporte, agregarla a "Sin Categoría"
                    $ventasPorFecha[$date]['categories']['Sin Categoría']['ventas'] += $detail->total;
                    $totalCategories['Sin Categoría'] += $detail->total;
                }
            }
        }

// Ordenar las comisiones de menor a mayor
        uasort($totalCommissions, function ($a, $b) {
            return $a <=> $b; // Ordenar de menor a mayor
        });

// Crear un arreglo ordenado para los totales por categoría (orden alfabético)
        $sortedTotalCategories = [];
        foreach ($categories as $category) {
            $sortedTotalCategories[$category->name] = $totalCategories[$category->name];
        }
        $sortedTotalCategories['Sin Categoría'] = $totalCategories['Sin Categoría'];

// Crear un arreglo ordenado para las comisiones (orden alfabético)
        $sortedTotalCommissions = [];
        foreach ($categories as $category) {
            $sortedTotalCommissions[$category->name] = $totalCommissions[$category->name];
        }
        $sortedTotalCommissions['Sin Categoría'] = $totalCommissions['Sin Categoría'];

// Agregar las ventas por fecha al reporte
        $report[] = [
            'ventasDiarias' => array_values($ventasPorFecha)
        ];

// Agregar los totales por categoría y las comisiones al final del reporte
        $report[] = [
            'total_by_category' => $sortedTotalCategories,
            'comission_by_category' => $sortedTotalCommissions
        ];

        $ventas = array_values($report);
        $empresa = Company::find(1);
        $id_sucursal = \Auth::user()->employee->branch_id;
        $sucursal = Branch::find($id_sucursal);
//        return response()->json($report);

        $pdf = Pdf::loadView('DTE.comission_sale_pdf', compact('ventas', 'empresa', 'sucursal','startDate','endDate','vendedor')) ->setPaper('letter', 'landscape');
//            ->setOptions([
//                'isHtml5ParserEnabled' => true,
//                'isRemoteEnabled' => true,
//            ]);

        return $pdf->stream("reporte_comision.pdf"); // El PDF se abre en una nueva pestaña


    }
}
