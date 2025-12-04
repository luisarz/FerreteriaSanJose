<?php

namespace App\Http\Controllers;

use Storage;
use App\Filament\Resources\CashboxOpens\CashboxOpenResource;
use App\Models\CashBoxOpen;
use App\Models\Company;
use App\Models\Sale;
use App\Models\SmallCashBoxOperation;
use App\Service\GetCashBoxOpenedService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Luecano\NumeroALetras\NumeroALetras;

class OrdenController extends Controller
{
    public function getConfiguracion()
    {
        $configuracion = Company::find(1);
        if ($configuracion) {
            return $configuracion;
        } else {
            return null;
        }
    }

    public function generarPdf($idVenta)
    {
        //abrir el json en DTEs
        $datos = Sale::with('customer', 'saleDetails', 'whereHouse', 'saleDetails.inventory', 'saleDetails.inventory.product', 'documenttype', 'seller', 'mechanic')
            ->find($idVenta);
        $empresa = $this->getConfiguracion();

        $formatter = new NumeroALetras();
        $montoLetras = $formatter->toInvoice($datos->sale_total, 2, 'DoLARES');
        $isLocalhost = in_array(request()->getHost(), ['127.0.0.1', 'localhost']);

        $pdf = Pdf::loadView('order.order-print-pdf', compact('datos', 'empresa', 'montoLetras'))
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => !$isLocalhost,
            ]); // Cargar vista y pasar datos

        return $pdf->stream("Orden-ventas-.{$idVenta}.pdf"); // El PDF se abre en una nueva pestaña

    }

    public function ordenGenerarTicket($idVenta)
    {
        //abrir el json en DTEs
        $datos = Sale::withTrashed()->with('customer', 'saleDetails', 'whereHouse', 'saleDetails.inventory', 'saleDetails.inventory.product', 'documenttype', 'seller', 'mechanic')->find($idVenta);

        if (!$datos) {
            abort(404, 'Orden no encontrada');
        }

        $empresa = $this->getConfiguracion();
        $logo = auth()->user()->employee->wherehouse->logo;
        $logoPath=Storage::url($logo);
        $isLocalhost = in_array(request()->getHost(), ['127.0.0.1', 'localhost']);

        $formatter = new NumeroALetras();
        $montoLetras = $formatter->toInvoice($datos->sale_total, 2, 'DoLARES');
        $pdf = Pdf::loadView('order.order-print-ticket', compact('datos', 'empresa', 'montoLetras','logoPath')) // Cargar vista y pasar datos

        ->setPaper([25, -10, 250, 1000]) // Tamaño personalizado
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => !$isLocalhost,
        ]);
        return $pdf->stream("Orden-ventas-.{$idVenta}.pdf"); // El PDF se abre en una nueva pestaña

    }

    public function closeClashBoxPrint($idCasboxClose)
    {

//        $sales = Sale::with('customer', 'seller')->where('status', '!=', 'anulado')->where('is_order', false)->where('cashbox_open_id', $idCasboxClose)->where('is_order', false);
//        $orders = Sale::with('customer', 'seller')->where('status', '!=', 'Finalizado')->where('is_order_closed_without_invoiced', false)->where('cashbox_open_id', $idCasboxClose)->where('is_order', false);
//        $ingresos = SmallCashBoxOperation::where('cash_box_open_id', $idCasboxClose)->where('operation', 'Ingreso')->whereNull('deleted_at');
//        $egresos = SmallCashBoxOperation::where('cash_box_open_id', $idCasboxClose)->where('operation', 'Egreso')->whereNull('deleted_at');
        $caja = CashBoxOpen::with('openEmployee', 'closeEmployee', 'cashbox')->find($idCasboxClose);
        $empresa = $this->getConfiguracion();

        $formatter = new NumeroALetras();
        $montoLetras = $formatter->toInvoice($caja->saldo_total_operaciones??0, 2, 'DÓLARES');
        $pdf = Pdf::loadView('print.closedcashbox-print-pdf', compact(
            'empresa',
            'caja',
            'montoLetras')); // Cargar vista y pasar datos

        return $pdf->stream("Cierre-de-caja-{$idCasboxClose}.pdf"); // El PDF se abre en una nueva pestaña

    }
}
