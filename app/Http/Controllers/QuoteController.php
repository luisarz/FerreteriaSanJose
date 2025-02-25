<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Luecano\NumeroALetras\NumeroALetras;

class QuoteController extends Controller
{
    public function printQuote($idVenta)
    {
        //abrir el json en DTEs
        $datos = Sale::with('customer', 'saleDetails', 'whereHouse', 'saleDetails.inventory', 'saleDetails.inventory.product', 'documenttype', 'seller','mechanic')->find($idVenta);
        $empresa =  $configuracion = Company::find(1);

        $formatter = new NumeroALetras();
        $montoLetras = $formatter->toInvoice($datos->sale_total, 2, 'DoLARES');
        $pdf = Pdf::loadView('quote.quote-print-pdf', compact('datos', 'empresa', 'montoLetras')); // Cargar vista y pasar datos


        return $pdf->stream("Orden-ventas-.{$idVenta}.pdf"); // El PDF se abre en una nueva pestaña

    }
}
