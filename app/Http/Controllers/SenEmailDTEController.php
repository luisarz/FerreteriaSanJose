<?php

namespace App\Http\Controllers;

use App\Models\HistoryDte;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Models\Sale;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\sendEmailDTE as sendDTEFiles;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SenEmailDTEController extends Controller
{
    public function SenEmailDTEController($idVenta): JsonResponse
    {
        $sale = Sale::with('customer', 'wherehouse', 'wherehouse.company')->find($idVenta);
        if (!$sale) {
            return response()->json([
                'status' => false,
                'message' => 'Venta no encontrada',
            ]);
        }

        $generationCode = $sale->generationCode;

        // Leer DTE de la base de datos
        $historyDte = HistoryDte::where('codigoGeneracion', $generationCode)->first();

        if (!$historyDte || !$historyDte->dte) {
            return response()->json([
                'status' => false,
                'message' => 'El DTE no existe en la base de datos',
                'body' => 'No se encontró el DTE con código: ' . $generationCode,
            ]);
        }

        $DTE = is_array($historyDte->dte) ? $historyDte->dte : json_decode($historyDte->dte, true);

        // Crear archivos temporales
        $tempDir = storage_path('app/temp/email');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $JsonPath = $tempDir . '/' . $generationCode . '.json';
        $PdfPath = $tempDir . '/' . $generationCode . '.pdf';

        try {
            // Guardar JSON temporal
            file_put_contents($JsonPath, json_encode($DTE, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            // Generar PDF temporal
            $this->generateTempPdf($DTE, $PdfPath, $sale);

            // Enviar correo
            Mail::to($sale->customer->email)
                ->send(new sendDTEFiles($JsonPath, $PdfPath, $sale));

            $data = [
                'status' => true,
                'message' => 'Email enviado exitosamente',
                'body' => 'Correo enviado a ' . $sale->customer->email,
            ];

        } catch (Exception $e) {
            $data = [
                'status' => false,
                'message' => 'Error al enviar el correo: ' . $e->getMessage(),
                'body' => 'Error al enviar el correo a ' . $sale->customer->email,
            ];
        } finally {
            // Limpiar archivos temporales
            @unlink($JsonPath);
            @unlink($PdfPath);
        }

        return response()->json($data);
    }

    private function generateTempPdf(array $DTE, string $pdfPath, Sale $sale): void
    {
        $tipoDocumento = $DTE['identificacion']['tipoDte'] ?? 'DESCONOCIDO';
        $logo = auth()->user()->employee->wherehouse->logo ?? $sale->wherehouse->logo;

        $tiposDTE = [
            '03' => 'COMPROBANTE DE CREDITO FISCAL',
            '01' => 'FACTURA',
            '02' => 'NOTA DE DEBITO',
            '04' => 'NOTA DE CREDITO',
            '05' => 'LIQUIDACION DE FACTURA',
            '11' => 'FACTURA DE EXPORTACION',
            '14' => 'SUJETO EXCLUIDO',
        ];

        $tipoDocumentoNombre = $tiposDTE[$tipoDocumento] ?? 'DOCUMENTO';
        $contenidoQR = "https://admin.factura.gob.sv/consultaPublica?ambiente=" . env('DTE_AMBIENTE_QR') . "&codGen=" . $DTE['identificacion']['codigoGeneracion'] . "&fechaEmi=" . $DTE['identificacion']['fecEmi'];

        $datos = [
            'empresa' => $DTE["emisor"],
            'DTE' => $DTE,
            'tipoDocumento' => $tipoDocumentoNombre,
            'logo' => Storage::url($logo),
        ];

        // Crear QR temporal
        $qrDir = storage_path('app/temp/QR');
        if (!file_exists($qrDir)) {
            mkdir($qrDir, 0755, true);
        }
        $qrPath = $qrDir . '/' . $DTE['identificacion']['codigoGeneracion'] . '.jpg';
        QrCode::size(300)->generate($contenidoQR, $qrPath);

        $qr = $qrPath;
        $isLocalhost = in_array(request()->getHost(), ['127.0.0.1', 'localhost']);

        $pdf = Pdf::loadView('DTE.dte-print-pdf', compact('datos', 'qr'))
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => !$isLocalhost,
            ]);

        $pdf->save($pdfPath);

        // Limpiar QR temporal
        @unlink($qrPath);
    }
}
