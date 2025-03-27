<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contingency;
use App\Models\HistoryDte;
use App\Models\Sale;
use DateTime;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Options;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class DTEController extends Controller
{
    public function generarDTE($idVenta)
    {
        if ($this->getConfiguracion() == null) {
            return response()->json(['message' => 'No se ha configurado la empresa']);
        }
        $venta = Sale::with('documenttype')->find($idVenta);
        if (!$venta) {
            return [
                'estado' => 'FALLO', // o 'ERROR'
                'mensaje' => 'Venta no encontrada',
            ];
        }
        if ($venta->is_dte) {
            return [
                'estado' => 'FALLO', // o 'ERROR'
                'mensaje' => 'DTE ya enviado',
            ];
        }
        if ($venta->documenttype->code == '01') {   //factura consumidor final
            return $this->facturaJson($idVenta);
        } elseif ($venta->documenttype->code == '03') {   //factura consumidor final

            return $this->CCFJson($idVenta);
        } else {
            return [
                'estado' => 'FALLO', // o 'ERROR'
                'mensaje' => 'Tipo de documento no soportado',
            ];
        }
    }

    public function getConfiguracion()
    {
        $configuracion = Company::find(1);
        if ($configuracion) {
            return $configuracion;
        } else {
            return null;
        }
    }


    function facturaJson($idVenta)
    {
        $factura = Sale::with('wherehouse.stablishmenttype', 'documenttype', 'seller', 'customer', 'customer.economicactivity', 'customer.departamento', 'customer.documenttypecustomer', 'salescondition', 'paymentmethod', 'saleDetails', 'saleDetails.inventory.product')->find($idVenta);

        $establishmentType = trim($factura->wherehouse->stablishmenttype->code);
        $conditionCode = trim($factura->salescondition->code);
        $receptor = [
            "documentType" => null,//$factura->customer->documenttypecustomer->code ?? null,
            "documentNum" => null,//$factura->customer->dui ?? $factura->customer->nit,
            "nrc" => null,//str_replace("-","",$factura->customer->nrc) ?? null,
            "name" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
            "phoneNumber" => isset($factura->customer) ? str_replace(["(", ")", "-", " "], "", $factura->customer->phone ?? '') : null,
            "email" => isset($factura->customer) ? trim($factura->customer->email ?? '') : null,
            "economicAtivity" => isset($factura->customer->economicactivity) ? trim($factura->customer->economicactivity->code ?? '') : null,
            "address" => isset($factura->customer) ? trim($factura->customer->address ?? '') : null,
            "codeCity" => isset($factura->customer->departamento) ? trim($factura->customer->departamento->code ?? '') : null,
            "codeMunicipality" => isset($factura->customer->distrito) ? trim($factura->customer->distrito->code ?? '') : null,
        ];
        $extencion = [
            "deliveryName" => isset($factura->seller) ? trim($factura->seller->name ?? '') . " " . trim($factura->seller->last_name ?? '') : null,
            "deliveryDoc" => isset($factura->seller) ? str_replace("-", "", $factura->seller->dui ?? '') : null,
        ];
        $items = [];
        $i = 1;
        foreach ($factura->saleDetails as $detalle) {
            $codeProduc = str_pad($detalle->inventory_id, 10, '0', STR_PAD_LEFT);
            $items[] = [
                "itemNum" => $i,
                "itemType" => 1,
                "docNum" => null,
                "code" => $codeProduc,
                "tributeCode" => null,
                "description" => $detalle->inventory->product->name,
                "quantity" => doubleval($detalle->quantity),
                "unit" => 1,
                "except" => false,
                "unitPrice" => doubleval(number_format($detalle->price, 8, '.', '')),
                "discountPercentage" => doubleval(number_format($detalle->discount, 8, '.', '')),
                "discountAmount" => doubleval(number_format(0, 8, '.', '')),
                "exemptSale" => doubleval(number_format(0, 8, '.', '')),
                "tributes" => null,
                "psv" => doubleval(number_format($detalle->price, 8, '.', '')),
                "untaxed" => doubleval(number_format(0, 8, '.', '')),
            ];
            $i++;
        }
        $branchId = auth()->user()->employee->branch_id ?? null;
        if (!$branchId) {
            return false;
        }
        $exiteContingencia = Contingency::where('warehouse_id', $branchId)
            ->where('is_close', 0)->first();
        $uuidContingencia = null;
        $transmissionType = 1;
        if ($exiteContingencia) {
            $exiteContingencia = $exiteContingencia->uuid_hacienda;
            $transmissionType = 2;
        }
        $dte = [
            "documentType" => "01",
            "transmissionType" => $transmissionType,
            "contingency" => $exiteContingencia,
            "invoiceId" => intval($factura->document_internal_number),
            "establishmentType" => $establishmentType,
            "conditionCode" => $conditionCode,
            "receptor" => $receptor,
            "extencion" => $extencion,
            "items" => $items
        ];

//        $dteJSON = json_encode($dte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
//        return response()->json($dte);

        $responseData = $this->SendDTE($dte, $idVenta);

//        return response()->json($responseData);

        if (isset($responseData["estado"]) == "RECHAZADO") {
            return [
                'estado' => 'FALLO', // o 'ERROR'
                'response' => $responseData,
                'mensaje' => 'DTE falló al enviarse: ' . implode(', ', $responseData['observaciones'] ?? []), // Concatenar observaciones
            ];
        } else {
            $this->saveJson($responseData, $idVenta);
            return [
                'estado' => 'EXITO',
                'mensaje' => 'DTE enviado correctamente',
            ];
        }
    }

    function CCFJson($idVenta)
    {
        $factura = Sale::with('wherehouse.stablishmenttype', 'documenttype', 'seller', 'customer', 'customer.economicactivity', 'customer.departamento', 'customer.documenttypecustomer', 'salescondition', 'paymentmethod', 'saleDetails', 'saleDetails.inventory.product')->find($idVenta);


        $establishmentType = trim($factura->wherehouse->stablishmenttype->code);
        $conditionCode = trim($factura->salescondition->code);
        $receptor = [
            "documentType" => trim($factura->customer->documenttypecustomer->code) ?? null,
            "documentNum" => trim($factura->customer->nit),
            "nit" => trim(str_replace("-", '', $factura->customer->nit)) ?? null,
            "nrc" => trim(str_replace("-", "", $factura->customer->nrc)) ?? null,
            "name" => trim($factura->customer->name) . " " . trim($factura->customer->last_name) ?? null,
            "phoneNumber" => isset($factura->customer) ? str_replace(["(", ")", "-", " "], "", $factura->customer->phone ?? '') : null,
            "email" => trim($factura->customer->email) ?? null,
            "address" => trim($factura->customer->address) ?? null,
            "businessName" => null,
            "codeCity" => trim($factura->customer->departamento->code) ?? null,
            "codeMunicipality" => trim($factura->customer->distrito->code) ?? null,
            "economicAtivity" => trim($factura->customer->economicactivity->code ?? null),
        ];
        $extencion = [
            "deliveryName" => trim($factura->seller->name) . " " . trim($factura->seller->last_name) ?? null,
            "deliveryDoc" => trim(str_replace("-", "", $factura->seller->dui)),
        ];
        $items = [];
        $i = 1;
        foreach ($factura->saleDetails as $detalle) {
            $codeProduc = str_pad($detalle->inventory_id, 10, '0', STR_PAD_LEFT);
            $tributes = ["20"];
            $items[] = [
                "itemNum" => intval($i),
                "itemType" => 1,
                "docNum" => null,
                "code" => $codeProduc,
                "tributeCode" => null,
                "description" => trim($detalle->inventory->product->name),
                "quantity" => doubleval($detalle->quantity),
                "unit" => 1,
                "except" => false,
                "unitPrice" => doubleval(number_format($detalle->price, 8, '.', '')),
                "discountAmount" => doubleval(number_format(0, 8, '.', '')),
                "discountPercentage" => doubleval(number_format($detalle->discount, 2, '.', '')),
                "exemptSale" => doubleval(number_format(0, 8, '.', '')),
                "tributes" => $tributes,
                "psv" => doubleval(number_format($detalle->price, 8, '.', '')),
                "untaxed" => doubleval(number_format(0, 8, '.', '')),
            ];
            $i++;
        }
        $dte = [
            "documentType" => "03",
            "invoiceId" => intval($factura->id),
            "establishmentType" => trim($establishmentType),
            "conditionCode" => trim($conditionCode),
            "receptor" => $receptor,
            "extencion" => $extencion,
            "items" => $items
        ];

//        return response()->json($dte);


        $responseData = $this->SendDTE($dte, $idVenta);
        if (isset($responseData["estado"]) == "RECHAZADO") {
            return [
                'estado' => 'FALLO', // o 'ERROR'
                'mensaje' => 'DTE falló al enviarse: ' . implode(', ', $responseData['observaciones'] ?? []), // Concatenar observaciones
            ];
        } else {
            $this->saveJson($responseData, $idVenta);


            return [
                'estado' => 'EXITO',
                'mensaje' => 'DTE enviado correctamente',
            ];
        }
    }

    function SendDTE($dteData, $idVenta) // Assuming $dteData is the data you need to send
    {
        set_time_limit(0);
        try {
            $urlAPI = 'http://api-fel-sv-dev.olintech.com/api/DTE/generateDTE'; // Set the correct API URL
            $apiKey = $this->getConfiguracion()->api_key; // Assuming you retrieve the API key from your config

            // Convert data to JSON format
            $dteJSON = json_encode($dteData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $urlAPI,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $dteJSON,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'apiKey: ' . $apiKey
                ),
            ));

            $response = curl_exec($curl);
//            dd($response);

            return response()->json($response);

            // Check for cURL errors
            if ($response === false) {
                return [
                    'estado' => 'RECHAZADO',
                    'response' => false,
                    'code' => curl_getinfo($curl, CURLINFO_HTTP_CODE),
                    'mensaje' => "Ocurrio un eror" . curl_error($curl)
                ];
            }

            //fecha contingencia
            //estado contingencia
            //

            curl_close($curl);

            $responseData = json_decode($response, true);
//dd($responseData);

            $responseHacienda = (isset($responseData["estado"]) == "RECHAZADO") ? $responseData : $responseData["respuestaHacienda"];
//            dd($responseData['identificacion']['numeroControl']);
            $falloDTE = new HistoryDte;
            $ventaID = intval($idVenta);
            $falloDTE->sales_invoice_id = $ventaID;
            $falloDTE->version = $responseHacienda["version"] ?? 0;
            $falloDTE->ambiente = $responseHacienda["ambiente"] ?? 0;
            $falloDTE->versionApp = $responseHacienda["versionApp"] ?? 0;
            $falloDTE->estado = $responseHacienda["estado"] ?? null;
            $falloDTE->codigoGeneracion = $responseHacienda["codigoGeneracion"] ?? null;
            $falloDTE->selloRecibido = $responseHacienda["selloRecibido"] ?? null;
            $falloDTE->num_control = $responseData["identificacion"]['numeroControl'] ?? null;
            if (isset($responseHacienda["fhProcesamiento"])) {
                $fhProcesamiento = DateTime::createFromFormat('d/m/Y H:i:s', $responseHacienda["fhProcesamiento"]);
                $falloDTE->fhProcesamiento = $fhProcesamiento ? $fhProcesamiento->format('Y-m-d H:i:s') : null;
            } else {
                $falloDTE->fhProcesamiento = null;
            }

            $falloDTE->clasificaMsg = $responseHacienda["clasificaMsg"] ?? null;
            $falloDTE->codigoMsg = $responseHacienda["codigoMsg"] ?? null;
            $falloDTE->descripcionMsg = $responseHacienda["descripcionMsg"] ?? null;
            $falloDTE->observaciones = json_encode($responseHacienda["observaciones"] ?? $responseHacienda["descripcion"]);
            $falloDTE->dte = $responseData ?? null;
            $falloDTE->save();
            return $responseData;

        } catch (Exception $e) {
            $data = [
                'estado' => 'RECHAZADO',
                'mensaje' => "Ocurrio un eror " . $e->getMessage()
            ];
            return $data;
        }
    }


    public function anularDTE($idVenta): array|\Illuminate\Http\JsonResponse
    {


        if ($this->getConfiguracion() == null) {
            return response()->json(['message' => 'No se ha configurado la empresa']);
        }
        $venta = Sale::with([
            'wherehouse.stablishmenttype',
            'seller',
            'documenttype',
            'salescondition',
            'paymentmethod',
            'dteProcesado' => function ($query) {
                $query->where('estado', 'PROCESADO');
            }
        ])->find($idVenta);

        if (!$venta) {
            return [
                'estado' => 'FALLO', // o 'ERROR'
                'mensaje' => 'Venta no encontrada',
            ];
        }
        if (!$venta->is_dte) {
            return [
                'estado' => 'FALLO', // o 'ERROR'
                'mensaje' => 'DTE no generado aun',
            ];
        }

        if ($venta->status == "Anulado") {
            return [
                'estado' => 'FALLO', // o 'ERROR'
                'mensaje' => 'DTE Ya fue anulado ',
            ];
        }

        $codigoGeneracion = $venta->dteProcesado->codigoGeneracion;
        $establishmentType = trim($venta->wherehouse->stablishmenttype->code);
        $user = \Auth::user()->employee;
        $dte = [
            "codeGeneration" => $codigoGeneracion,
            "codeGenerationR" => null,
            "description" => "Anulación de la operación",
            "establishmentType" => $establishmentType,
            "type" => 2,
            "responsibleName" => $venta->seller->name . " " . $venta->seller->lastname,
            "responsibleDocType" => "13",
            "responsibleDocNumber" => trim(str_replace("-", "", $venta->seller->dui)),
            "requesterName" => $user->name . " " . $user->lastname,
            "requesterDocType" => "13",
            "requesterDocNumber" => trim(str_replace("-", "", $user->dui)),
        ];


//        return response()->json($dte);
        $responseData = $this->SendAnularDTE($dte, $idVenta);
        $reponse_anular = $responseData['response_anular'] ?? null;
        if (isset($reponse_anular['estado']) == "RECHAZADO") {
            return [
                'estado' => 'FALLO', // o 'ERROR'
                'mensaje' => 'DTE falló al enviarse: ' . implode(', ', $responseData['observaciones'] ?? []), // Concatenar observaciones
                'descripcionMsg' => $reponse_anular['descripcionMsg'] ?? null,
                'codigoGeneracion' => $codigoGeneracion['codigoGeneracion'] ?? null
            ];
        } else {
            $venta = Sale::find($idVenta);
            $venta->sale_status = "Anulado";
            $venta->save();
            return [
                'estado' => 'EXITO',
                'mensaje' => 'DTE ANULADO correctamente',
            ];
        }
    }

    function SendAnularDTE($dteData, $idVenta) // Assuming $dteData is the data you need to send
    {
        try {
            $urlAPI = 'http://api-fel-sv-dev.olintech.com/api/DTE/cancellationDTE'; // Set the correct API URL
            $apiKey = $this->getConfiguracion()->api_key; // Assuming you retrieve the API key from your config

            // Convert data to JSON format
            $dteJSON = json_encode($dteData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => $urlAPI,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $dteJSON,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'apiKey: ' . $apiKey
                ),
            ));

            $response = curl_exec($curl);

            // Check for cURL errors
            if ($response === false) {
                $data = [
                    'estado' => 'RECHAZADO ',
                    'mensaje' => "Ocurrio un eror" . curl_error($curl)
                ];
                return $data;
            }

            curl_close($curl);

            $responseData = json_decode($response, true);
//            dd  ($responseData);
            $responseData['response_anular'] = json_decode($responseData['descripcion'], true) ?? [];
//            dd($responseData);
            $response_anular = $responseData['response_anular'] ?? [];

            $observacion_fail = str_replace(['[', ']', '"'], '', $response_anular['descripcionMsg'] ?? '');

            $responseHacienda = ($responseData["estado"] == "RECHAZADO") ? $responseData : $responseData["respuestaHacienda"];
            $falloDTE = new HistoryDte;
            $falloDTE->sales_invoice_id = $idVenta;
            $falloDTE->version = $responseHacienda["version"] ?? 2;
            $falloDTE->ambiente = $responseHacienda["ambiente"] ?? "00";
            $falloDTE->versionApp = $responseHacienda["versionApp"] ?? 2;
            $falloDTE->estado = $responseHacienda["estado"] ?? "RECHAZADO";
            $falloDTE->codigoGeneracion = $responseHacienda["codigoGeneracion"] ?? null;
            $falloDTE->selloRecibido = $responseHacienda["selloRecibido"] ?? null;

            $fhProcesamiento = DateTime::createFromFormat('d/m/Y H:i:s', $responseHacienda["fhProcesamiento"] ?? null);
            $falloDTE->fhProcesamiento = $fhProcesamiento ? $fhProcesamiento->format('Y-m-d H:i:s') : null;

            $falloDTE->clasificaMsg = $responseHacienda["clasificaMsg"] ?? null;
            $falloDTE->codigoMsg = $responseHacienda["codigoMsg"] ?? null;
            $falloDTE->descripcionMsg = $responseHacienda["descripcionMsg"] ?? null;

            $falloDTE->observaciones = isset($responseHacienda["observaciones"])
                ? (is_array($responseHacienda["observaciones"])
                    ? json_encode($responseHacienda["observaciones"], JSON_UNESCAPED_UNICODE)
                    : $responseHacienda["observaciones"])
                : $observacion_fail;

            $falloDTE->dte = json_encode($responseData, JSON_UNESCAPED_UNICODE);

            $falloDTE->save();

            return $responseData;

        } catch (Exception $e) {
            $data = [
                'estado' => 'RECHAZADO ',
                'mensaje' => "Ocurrio un eror" . $e->getMessage()
            ];
            return $data;
        }
    }

    public function printDTETicket($codGeneracion)
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
                '06' => 'LIQUIDACION DE FACTURA SIMPLIFICADA'
            ];
            $tipoDocumento = $this->searchInArray($tipoDocumento, $tiposDTE);
            $contenidoQR = "https://admin.factura.gob.sv/consultaPublica?ambiente=00&codGen=" . $DTE['identificacion']['codigoGeneracion'] . "&fechaEmi=" . $DTE['identificacion']['fecEmi'];

            $datos = [
                'empresa' => $DTE["emisor"], // O la función correspondiente para cargar datos globales de la empresa.
                'DTE' => $DTE,
                'tipoDocumento' => $tipoDocumento,
                'logo' => Storage::url($logo),
            ];

            // dd($datos['logo']);

            $directory = storage_path('app/public/QR');

            if (!file_exists($directory)) {
                mkdir($directory, 0755, true); // Create the directory with proper permissions
            }
            $path = $directory . '/' . $DTE['identificacion']['codigoGeneracion'] . '.jpg';


            QrCode::size(300)->generate($contenidoQR, $path);
            if (file_exists($path)) {
                $qr = Storage::url("QR/{$DTE['identificacion']['codigoGeneracion']}.jpg");
            } else {
                throw new \Exception("Error: El archivo QR no fue guardado correctamente en {$path}");
            }


            $pdf = Pdf::loadView('DTE.dte-print-ticket', compact('datos', 'qr'))
                ->setOptions([
//                    'isHtml5ParserEnabled' => true,
//                    'isRemoteEnabled' => true,
                ]);

            $pdfPage = Pdf::loadView('DTE.dte-print-pdf', compact('datos', 'qr'))
                ->setOptions([
//                    'isHtml5ParserEnabled' => true,
//                    'isRemoteEnabled' => true,
                ]);

            $pathPage = storage_path("app/public/DTEs/{$codGeneracion}.pdf");


            $pdfPage->save($pathPage);
            $pdf->set_paper(array(0, 0, 250, 1000)); // Custom paper size

            return $pdf->stream("{$codGeneracion}.pdf");
        } else {
            return response()->json(['error' => 'El archivo no existe.'], 404); // Retornar error 404
        }


    }

    public function printDTEPdf($codGeneracion)
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
                '06' => 'LIQUIDACION DE FACTURA SIMPLIFICADA'
            ];
            $tipoDocumento = $this->searchInArray($tipoDocumento, $tiposDTE);
            $contenidoQR = "https://admin.factura.gob.sv/consultaPublica?ambiente=00&codGen=" . $DTE['identificacion']['codigoGeneracion'] . "&fechaEmi=" . $DTE['identificacion']['fecEmi'];

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
                throw new \Exception("Error: El archivo QR no fue guardado correctamente en {$path}");
            }

            $pdf = Pdf::loadView('DTE.dte-print-pdf', compact('datos', 'qr'))
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                ]);

            return $pdf->stream("{$codGeneracion}.pdf"); // El PDF se abre en una nueva pestaña
        } else {
            return response()->json(['error' => 'El archivo no existe.'], 404); // Retornar error 404
        }


    }

    public function getDTE($codGeneracion)
    {
        try {
            $dte = Http::asForm()
                ->withHeaders(['accept' => '*/*', 'apiKey' => 'c561a756-f332-45d1-bff2-9d59022a6eb5',])
                ->post('http://api-fel-sv-dev.olintech.com/api/DTE/getDTE',
                    [
                        'generationCode' => '490C8111-5056-43BA-8686-602E216A7CDD',
                    ]);
            if ($dte) {
                return json_decode($dte, true);
            } else {
                $data = [
                    'estado' => 'RECHAZADO ',
                    'mensaje' => "Ocurrio un eror"
                ];
                return $data;
            }
        } catch (ConnectionException $e) {
            return $e->getMessage();
        }


    }

    /**
     * @param mixed $responseData
     * @param $idVenta
     * @return void
     */
    public function saveJson(mixed $responseData, $idVenta): void
    {
        $fileName = "DTEs/{$responseData['respuestaHacienda']['codigoGeneracion']}.json";
        $jsonContent = json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        Storage::disk('public')->put($fileName, $jsonContent);

        $venta = Sale::find($idVenta);
        $venta->is_dte = true;
        $venta->generationCode = $responseData["respuestaHacienda"]["codigoGeneracion"] ?? null;
        $venta->jsonUrl = $fileName;
        $venta->save();
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
