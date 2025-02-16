<?php

namespace App\Http\Controllers;

use App\Models\Transfer;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use App\Models\Company;
use App\Models\Sale;
use Luecano\NumeroALetras\NumeroALetras;

class TransferController extends Controller
{
    public function printTransfer($id)
    {
        $transfer=Transfer::with('wherehouseFrom','wherehouseTo','userSend','userRecive','transferDetails')->find($id);
        $empresa=Company::first();
        $pdf = Pdf::loadView('Transfer.order-print-pdf', compact('transfer','empresa' )); // Cargar vista y pasar datos
        return $pdf->stream("{'Trasnfer-'.$transfer->id}.pdf"); // El PDF se abre en una nueva pestaña


    }
}
