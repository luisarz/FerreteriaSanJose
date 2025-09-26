<?php

namespace App\Http\Controllers;

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
use Illuminate\Support\Facades\Storage;


class ReportsController extends Controller
{
    public function saleReportFact($doctype,$startDate, $endDate): BinaryFileResponse
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
        $sales = Sale::select('id')
            ->where('is_dte', '1')
            ->whereIn('document_type_id', [1, 3, 5, 11, 14])//1- Fac 3-CCF 5-NC 11-FExportacion 14-Sujeto excluido
            ->whereBetween('operation_date', [$startDate, $endDate])
            ->orderBy('operation_date', 'asc')
            ->with(['dteProcesado' => function ($query) {
                $query->select('sales_invoice_id', 'num_control', 'selloRecibido', 'codigoGeneracion', 'dte')
                    ->whereNotNull('selloRecibido');
            }])
            ->get();


        try {

            $failed = array();
            $failedCount = 0;
            //Limpiamops los incorrectos
            foreach ($sales as $sale) {
                $codgeneration = $sale->dteProcesado->codigoGeneracion;
                $filePath = storage_path("app/public/DTEs/{$codgeneration}.json");
                if (file_exists($filePath) && filesize($filePath) < 2048) {//Eliminar si pesa menos de 2kb
                    unlink($filePath);
                    $failedCount++;
                    $failed [] = $codgeneration;
                }
            }

            $zipFileName = 'dte_' . $startDate . '-' . $endDate . '.zip';
            $zipPath = storage_path("app/public/{$zipFileName}");
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $added = false;
                foreach ($sales as $sale) {
                    $codgeneration = $sale->dteProcesado->codigoGeneracion;
                    $filePath = storage_path("app/public/DTEs/{$codgeneration}.json");
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, "{$codgeneration}.json");
                        $added = true;

                    } else {

                        //Lo almacena
                        $dteController = new DTEController();
                        $dteController->saveRestoreJson($sale->dteProcesado->dte, $codgeneration);
                        $filePath = storage_path("app/public/DTEs/{$codgeneration}.json");
                        if (file_exists($filePath)) {
                            $zip->addFile($filePath, "{$codgeneration}.json");
                            $added = true;
                        }
                    }
                }

                if ($failedCount > 0) {
                    $failedList = implode("\n", $failed);
                    $zip->addFromString('README.txt', "No se encontraron archivos JSON para los siguientes archivos:\n{$failedList}");

                }

                $zip->close();
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
        $sales = Sale::select('id')
            ->where('is_dte', '1')
            ->whereIn('document_type_id', [1, 3, 5, 11, 14])//1- Fac 3-CCF 5-NC 11-FExportacion 14-Sujeto excluido
            ->whereBetween('operation_date', [$startDate, $endDate])
            ->orderBy('operation_date', 'asc')
            ->with(['dteProcesado' => function ($query) {
                $query->select('sales_invoice_id', 'num_control', 'selloRecibido', 'codigoGeneracion', 'dte')
                    ->whereNotNull('selloRecibido');
            }])
            ->get();

        try {

            $failed = array();
            $failedCount = 0;
            //Limpiamops los incorrectos
            foreach ($sales as $sale) {
                $codgeneration = $sale->dteProcesado->codigoGeneracion;
                $filePath = storage_path("app/public/DTEs/{$codgeneration}.json");
                if (file_exists($filePath) && filesize($filePath) < 2048) {//Eliminar si pesa menos de 2kb
                    unlink($filePath);
                    $failedCount++;
                    $failed [] = $codgeneration;
                }
            }

            $zipFileName = 'pdf_' . $startDate . '-' . $endDate . '.zip';
            $zipPath = storage_path("app/public/{$zipFileName}");
            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                $added = false;
                foreach ($sales as $sale) {
                    $codgeneration = $sale->dteProcesado->codigoGeneracion;
                    $filePath = storage_path("app/public/DTEs/{$codgeneration}.pdf");
                    if (file_exists($filePath)) {
                        $zip->addFile($filePath, "{$codgeneration}.pdf");
                        $added = true;

                    } else {

                        //Lo almacena
                        $this->generatePdf($codgeneration);
                        $filePath = storage_path("app/public/DTEs/{$codgeneration}.pdf");
                        if (file_exists($filePath)) {
                            $zip->addFile($filePath, "{$codgeneration}.pdf");
                            $added = true;
                        }
                    }
                }

                if ($failedCount > 0) {
                    $failedList = implode("\n", $failed);
                    $zip->addFromString('README.txt', "No se encontraron archivos JSON para los siguientes archivos:\n{$failedList}");

                }

                $zip->close();
            } else {
                return response()->json(['error' => 'No se pudo crear el archivo ZIP.'], 500);
            }

            return response()->download($zipPath)->deleteFileAfterSend(true);

        } catch (Exception $e) {
            return response()->json(['error' => 'Error al descargar el archivo ZIP: ' . $e->getMessage()], 500);
        }
    }

    function generatePdf($codGeneracion): bool
    {

        $fileName = "/DTEs/{$codGeneracion}.json";

        if (Storage::disk('public')->exists($fileName)) {
            $fileContent = Storage::disk('public')->get($fileName);
            $DTE = json_decode($fileContent, true); // Decodificar JSON en un array asociativo
            $tipoDocumento = $DTE['identificacion']['tipoDte'] ?? 'DESCONOCIDO';
            $logo = auth()->user()->employee->wherehouse->logo;
            $tiposDTE = [
                '03' => 'COMPROBANTE DE CREDITO  FISCAL',
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
            $tipoDocumento = $this->searchInArray($tipoDocumento, $tiposDTE);
            $contenidoQR = "https://admin.factura.gob.sv/consultaPublica?ambiente=" . env('DTE_AMBIENTE_QR') . "&codGen=" . $DTE['identificacion']['codigoGeneracion'] . "&fechaEmi=" . $DTE['identificacion']['fecEmi'];

            $datos = [
                'empresa' => $DTE["emisor"], // O la función correspondiente para cargar datos globales de la empresa.
                'DTE' => $DTE,
                'tipoDocumento' => $tipoDocumento,
                'logo' => Storage::url($logo),
            ];


            $directory = storage_path('app/public/QR');

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true); // Create the directory with proper permissions
            }
            $path = $directory . '/' . $DTE['identificacion']['codigoGeneracion'] . '.jpg';


            QrCode::size(300)->generate($contenidoQR, $path);

            if (file_exists($path)) {
                $qr = Storage::url("QR/{$DTE['identificacion']['codigoGeneracion']}.jpg");
            } else {
                throw new Exception("Error: El archivo QR no fue guardado correctamente en {$path}");
            }
            $isLocalhost = in_array(request()->getHost(), ['127.0.0.1', 'localhost']);

            $pdf = Pdf::loadView('DTE.dte-print-pdf', compact('datos', 'qr'))
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => !$isLocalhost,
                ]);
            $pathPage = storage_path("app/public/DTEs/{$codGeneracion}.pdf");

            $pdf->save($pathPage);
            return true;
        } else {
            return false;
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
