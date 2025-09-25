<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Luecano\NumeroALetras\NumeroALetras;

class QuoteController extends Controller
{
    public function printQuote($idVenta)
    {
        // Cargar datos de la venta
        $datos = Sale::with([
            'customer',
            'saleDetails',
            'whereHouse',
            'saleDetails.inventory',
            'saleDetails.inventory.product',
            'documenttype',
            'seller',
            'mechanic'
        ])->find($idVenta);

        // Datos de la empresa y logo
        $empresa = Company::find(1);
        $logo = auth()->user()->employee->wherehouse->logo ?? null;
        $logo_url = $logo ? Storage::url($logo) : null;

        // Convertir monto a letras
        $formatter = new NumeroALetras();
        $montoLetras = $formatter->toInvoice($datos->sale_total, 2, 'DOLARES');

        // Detectar si es localhost para habilitar recursos remotos
        $isLocalhost = in_array(request()->getHost(), ['127.0.0.1', 'localhost']);

        // Generar PDF con vista
        $pdf = Pdf::loadView('quote.quote-print-pdf', compact('datos', 'empresa', 'montoLetras', 'logo_url'))
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => !$isLocalhost,
            ]);

        // Retornar PDF en el navegador
        return $pdf->stream("Cotizacion-{$idVenta}.pdf");
    }

}
