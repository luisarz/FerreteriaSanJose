<?php

namespace App\Http\Controllers;

use App\Models\adjustmentInventory;
use App\Models\Company;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Luecano\NumeroALetras\NumeroALetras;

class AdjustementInventory extends Controller
{
    //
    public function salidaPrintTicket($id_adjustement)
    {
        //abrir el json en DTEs
        $datos = adjustmentInventory::with('branch', 'adjustItems', 'branch', 'adjustItems.inventory', 'adjustItems.inventory.product')
            ->find($id_adjustement);
        $logo = auth()->user()->employee->wherehouse->logo;
        $logoPath=\Storage::url($logo);
        $empresa = $configuracion = Company::find(1);
        $formatter = new NumeroALetras();
        $montoLetras = $formatter->toInvoice($datos->monto, 2, 'DoLARES');
        $isLocalhost = in_array(request()->getHost(), ['127.0.0.1', 'localhost']);

        $pdf = Pdf::loadView('entradas-salidas.entrada-print-ticket', compact('datos', 'empresa', 'montoLetras','logoPath')) // Cargar vista y pasar datos

        ->setPaper([25, -10, 250, 1000]) // Tamaño personalizado
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => !$isLocalhost,
        ]);
        return $pdf->stream("Orden-ventas-.{$id_adjustement}.pdf"); // El PDF se abre en una nueva pestaña

    }
}
