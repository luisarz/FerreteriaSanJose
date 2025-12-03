<?php

namespace App\Http\Controllers;

use App\Models\HistoryDte;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Http\JsonResponse;
use Exception;
use App\Exports\SalesExportCCF;
use App\Exports\SalesExportFac;
use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;


class ReportsController extends Controller
{
    public function saleReportFact($doctype, $startDate, $endDate): BinaryFileResponse
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);
        $documentType = intval($doctype);

        return Excel::download(
            new SalesExportFac($documentType, $startDate, $endDate),
            "ventas-{$startDate->format('Y-m-d')}-{$endDate->format('Y-m-d')}.xlsx"
        );
    }

    public function downloadJson($startDate, $endDate): BinaryFileResponse|JsonResponse
    {
        set_time_limit(0);

        // Obtener DTEs directamente de history_dtes (incluye contingencias sin sello)
        $historyDtes = HistoryDte::whereNotNull('codigoGeneracion')
            ->whereNotNull('dte')
            ->where('estado', 'PROCESADO')
            ->whereHas('salesInvoice', function ($query) use ($startDate, $endDate) {
                $query->where('is_dte', '1')
                    ->whereIn('document_type_id', [1, 3, 5, 11, 14])
                    ->whereBetween('operation_date', [$startDate, $endDate]);
            })
            ->get();

        if ($historyDtes->isEmpty()) {
            return response()->json(['error' => 'No se encontraron DTEs para el rango de fechas especificado.'], 404);
        }

        try {
            // Crear directorio temporal
            $tempDir = storage_path('app/temp/zip_json');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $zipFileName = 'dte_' . $startDate . '-' . $endDate . '.zip';
            $zipPath = storage_path("app/temp/{$zipFileName}");

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $tempFiles = [];

                foreach ($historyDtes as $historyDte) {
                    $codGeneracion = $historyDte->codigoGeneracion;
                    $dte = is_array($historyDte->dte) ? $historyDte->dte : json_decode($historyDte->dte, true);

                    // Crear archivo temporal
                    $tempFilePath = $tempDir . '/' . $codGeneracion . '.json';
                    file_put_contents($tempFilePath, json_encode($dte, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    $tempFiles[] = $tempFilePath;

                    $zip->addFile($tempFilePath, "{$codGeneracion}.json");
                }

                $zip->close();

                // Limpiar archivos temporales después de cerrar el ZIP
                foreach ($tempFiles as $tempFile) {
                    @unlink($tempFile);
                }
                @rmdir($tempDir);

            } else {
                return response()->json(['error' => 'No se pudo crear el archivo ZIP.'], 500);
            }

            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al descargar el archivo ZIP: ' . $e->getMessage()], 500);
        }
    }

    public function downloadPdf($startDate, $endDate): BinaryFileResponse|JsonResponse
    {
        set_time_limit(0);

        // Obtener DTEs directamente de history_dtes (incluye contingencias sin sello)
        $historyDtes = HistoryDte::whereNotNull('codigoGeneracion')
            ->whereNotNull('dte')
            ->where('estado', 'PROCESADO')
            ->whereHas('salesInvoice', function ($query) use ($startDate, $endDate) {
                $query->where('is_dte', '1')
                    ->whereIn('document_type_id', [1, 3, 5, 11, 14])
                    ->whereBetween('operation_date', [$startDate, $endDate]);
            })
            ->get();

        if ($historyDtes->isEmpty()) {
            return response()->json(['error' => 'No se encontraron DTEs para el rango de fechas especificado.'], 404);
        }

        try {
            // Crear directorio temporal
            $tempDir = storage_path('app/temp/zip_pdf');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $zipFileName = 'pdf_' . $startDate . '-' . $endDate . '.zip';
            $zipPath = storage_path("app/temp/{$zipFileName}");

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $tempFiles = [];

                foreach ($historyDtes as $historyDte) {
                    $codGeneracion = $historyDte->codigoGeneracion;
                    $dte = is_array($historyDte->dte) ? $historyDte->dte : json_decode($historyDte->dte, true);

                    // Generar PDF temporal
                    $tempPdfPath = $this->generateTempPdf($dte, $codGeneracion, $tempDir);
                    if ($tempPdfPath) {
                        $tempFiles[] = $tempPdfPath;
                        $zip->addFile($tempPdfPath, "{$codGeneracion}.pdf");
                    }
                }

                $zip->close();

                // Limpiar archivos temporales después de cerrar el ZIP
                foreach ($tempFiles as $tempFile) {
                    @unlink($tempFile);
                }
                @rmdir($tempDir);

            } else {
                return response()->json(['error' => 'No se pudo crear el archivo ZIP.'], 500);
            }

            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al descargar el archivo ZIP: ' . $e->getMessage()], 500);
        }
    }

    private function generateTempPdf(array $DTE, string $codGeneracion, string $tempDir): ?string
    {
        try {
            $tipoDocumento = $DTE['identificacion']['tipoDte'] ?? 'DESCONOCIDO';
            $logo = auth()->user()->employee->wherehouse->logo ?? null;

            $tiposDTE = [
                '03' => 'COMPROBANTE DE CREDITO FISCAL',
                '01' => 'FACTURA',
                '02' => 'NOTA DE DEBITO',
                '04' => 'NOTA DE CREDITO',
                '05' => 'LIQUIDACION DE FACTURA',
                '06' => 'LIQUIDACION DE FACTURA SIMPLIFICADA',
                '08' => 'COMPROBANTE LIQUIDACION',
                '09' => 'DOCUMENTO CONTABLE DE LIQUIDACION',
                '11' => 'FACTURA DE EXPORTACION',
                '14' => 'SUJETO EXCLUIDO',
                '15' => 'COMPROBANTE DE DONACION'
            ];

            $tipoDocumentoNombre = $tiposDTE[$tipoDocumento] ?? 'DOCUMENTO';
            $contenidoQR = "https://admin.factura.gob.sv/consultaPublica?ambiente=" . env('DTE_AMBIENTE_QR') . "&codGen=" . $DTE['identificacion']['codigoGeneracion'] . "&fechaEmi=" . $DTE['identificacion']['fecEmi'];

            // Crear QR temporal
            $qrDir = storage_path('app/temp/QR');
            if (!file_exists($qrDir)) {
                mkdir($qrDir, 0755, true);
            }
            $qrPath = $qrDir . '/' . $codGeneracion . '.jpg';
            QrCode::size(300)->generate($contenidoQR, $qrPath);

            // Convertir imágenes a base64 para DomPDF
            $logoBase64 = null;
            if ($logo) {
                $logoFullPath = storage_path('app/public/' . $logo);
                if (file_exists($logoFullPath)) {
                    $logoData = file_get_contents($logoFullPath);
                    $logoMime = mime_content_type($logoFullPath);
                    $logoBase64 = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
                }
            }

            $qrBase64 = null;
            if (file_exists($qrPath)) {
                $qrData = file_get_contents($qrPath);
                $qrBase64 = 'data:image/png;base64,' . base64_encode($qrData);
            }

            $datos = [
                'empresa' => $DTE["emisor"],
                'DTE' => $DTE,
                'tipoDocumento' => $tipoDocumentoNombre,
                'logo' => $logoBase64,
            ];

            $qr = $qrBase64;

            $pdf = Pdf::loadView('DTE.dte-print-pdf', compact('datos', 'qr'))
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            $pdfPath = $tempDir . '/' . $codGeneracion . '.pdf';
            $pdf->save($pdfPath);

            // Limpiar QR temporal
            @unlink($qrPath);

            return $pdfPath;

        } catch (Exception $e) {
            return null;
        }
    }

    function searchInArray($clave, $array)
    {
        if (array_key_exists($clave, $array)) {
            return $array[$clave];
        } else {
            return 'Clave no encontrada';
        }
    }
}
