<?php

namespace App\Http\Controllers;

use Auth;
use Log;
use App\Helpers\KardexHelper;
use App\Models\Branch;
use App\Models\CashBoxCorrelative;
use App\Models\Company;
use App\Models\Contingency;
use App\Models\HistoryDte;
use App\Models\Inventory;
use App\Models\InventoryGrouped;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Service\GetCashBoxOpenedService;
use Aws\History;
use DateTime;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Options;

use SimpleSoftwareIO\QrCode\Facades\QrCode;


class DTEController extends Controller
{
    public function generarDTE($idVenta): array|JsonResponse
    {
        $configuracion = $this->getConfiguracion();
        if (!$configuracion) {
            return response()->json(['message' => 'No se ha configurado la empresa']);
        }

        $venta = Sale::with('documenttype')->find($idVenta);
        if (!$venta) {
            return $this->respuestaFallo('Venta no encontrada');
        }

        if ($venta->is_dte) {
            return $this->respuestaFallo('DTE ya enviado');
        }

        $documentTypes = [
            '01' => 'facturaJson',
            '03' => 'CCFJson',
            '04' => 'RemisionNotesJSON',
            '05' => 'CreditNotesJSON',
            '06' => 'DebitNotesJSON',
            '11' => 'ExportacionJson',
            '14' => 'sujetoExcluidoJson',
        ];

        $method = $documentTypes[$venta->documenttype->code] ?? null;

        return ($method && method_exists($this, $method))
            ? $this->$method($idVenta)
            : $this->respuestaFallo('Tipo de documento no soportado');
    }

    private function respuestaFallo($mensaje): array
    {
        return [
            'estado' => 'FALLO',
            'mensaje' => $mensaje,
        ];
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

    public function warehouse($wareHauseId)
    {
        return Branch::find($wareHauseId);
    }


    function facturaJson($idVenta): array|jsonResponse
    {
        $factura = Sale::with('wherehouse.stablishmenttype', 'documenttype', 'seller', 'customer', 'customer.economicactivity', 'customer.departamento', 'customer.documenttypecustomer', 'salescondition', 'paymentmethod', 'saleDetails', 'saleDetails.inventory.product')->find($idVenta);

        if ($factura->document_internal_number == 0 || $factura->document_internal_number == null) {
            $document_internal_number_new = 0;
            $document_type_id = $factura->document_type_id;
            $aperturaCaja = (new GetCashBoxOpenedService())->getOpenCashBox();
            if (!$aperturaCaja['status']) {
                return [
                    'estado' => 'FALLO',
                    'mensaje' => 'No hay caja aperturada para generar el documento'
                ];
            }
            $CashBoxCOrrelativeOpen = CashBoxCorrelative::where('cash_box_id', $aperturaCaja['id_caja'])->where('document_type_id', $document_type_id)->first();
            if ($CashBoxCOrrelativeOpen) {
                $document_internal_number_new = $CashBoxCOrrelativeOpen->current_number + 1;
            }
            $factura->update([
                'cashbox_open_id' => $aperturaCaja['id_apertura_caja'],
                'document_internal_number' => $document_internal_number_new,
                'operation_date' => now(),
            ]);
            $CashBoxCOrrelativeOpen->current_number = $document_internal_number_new;
            $CashBoxCOrrelativeOpen->save();
        }


        $establishmentType = trim($factura->wherehouse->stablishmenttype->code);
        $conditionCode = trim($factura->salescondition->code);
        $receptor = [
            "documentType" => null,//$factura->customer->documenttypecustomer->code ?? null,
            "documentNum" => null,//$factura->customer->dui ?? $factura->customer->nit,
            "nit" => null,
            "nrc" => null,
            "name" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
            "phoneNumber" => ($number = preg_replace('/\D/', '', $factura->customer?->phone ?? '')) && strlen($number) >= 8 ? $number : null,

            "email" => isset($factura->customer) ? trim($factura->customer->email ?? null) : null,
            "businessName" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
            "economicAtivity" => isset($factura->customer->economicactivity) ? trim($factura->customer->economicactivity->code ?? null) : null,
            "address" => isset($factura->customer) ? trim($factura->customer->address ?? null) : null,
            "codeCity" => isset($factura->customer->departamento) ? trim($factura->customer->departamento->code ?? null) : null,
            "codeMunicipality" => isset($factura->customer->distrito) ? trim($factura->customer->distrito->code ?? null) : null,
        ];
        $extencion = [
            "deliveryName" => isset($factura->seller) ? trim($factura->seller->name ?? '') . " " . trim($factura->seller->last_name ?? '') : null,
            "deliveryDoc" => isset($factura->seller) ? str_replace("-", "", $factura->seller->dui ?? '') : null,
        ];
        $items = [];
        $i = 1;
        foreach ($factura->saleDetails as $detalle) {
            $codeProduc = str_pad($detalle->inventory_id, 10, '0', STR_PAD_LEFT);
            $exent = !$detalle->inventory->product->is_taxed;
            $items[] = [
                "itemNum" => $i,
                "itemType" => 1,
                "docNum" => null,
                "code" => $codeProduc,
                "tributeCode" => null,
                "description" => $detalle->inventory->product->name . " " . $detalle->description,
                "quantity" => doubleval($detalle->quantity),
                "unit" => 1,
                "except" => $exent,
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
            return [
                'estado' => 'FALLO',
                'mensaje' => 'No hay datos de la sucursal o empresa configurados'
            ];
        }
        $exiteContingencia = Contingency::where('warehouse_id', $branchId)
            ->where('is_close', 0)->first();
        $uuidContingencia = null;
        $transmissionType = 1;
        if ($exiteContingencia) {
            $uuidContingencia = $exiteContingencia->uuid_hacienda;
            $transmissionType = 2;
        }
        $sucursal = $this->warehouse($factura->wherehouse_id);
        $dte = [
            "documentType" => "01",
            "invoiceId" => intval($factura->document_internal_number),
            "establishmentType" => $establishmentType,
            "conditionCode" => $conditionCode,
            "transmissionType" => $transmissionType,
            "contingency" => $uuidContingencia,
            "codeEstablishmentType" => trim($sucursal->establishment_type_code) ?? null,
            "codePosTerminal" => trim($sucursal->pos_terminal_code) ?? null,
            "economicAtivity" => trim($sucursal->economicactivity->code) ?? null,
            "emisorPhone" => trim($sucursal->phone) ?? null,
            "emisorAddress" => trim($sucursal->address) ?? null,
            "receptor" => $receptor,
            "extencion" => $extencion,
            "items" => $items
        ];

//        $dteJSON = json_encode($dte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
//        return response()->json($dte);

        return $this->processDTE($dte, $idVenta);
    }

    function CCFJson($idVenta): array|JsonResponse
    {
        $factura = Sale::with('wherehouse.stablishmenttype', 'documenttype', 'seller', 'customer', 'customer.economicactivity', 'customer.departamento', 'customer.documenttypecustomer', 'salescondition', 'paymentmethod', 'saleDetails', 'saleDetails.inventory.product')->find($idVenta);
        if ($factura->document_internal_number == 0 || $factura->document_internal_number == null) {
            $document_internal_number_new = 0;
            $document_type_id = $factura->document_type_id;
            $aperturaCaja = (new GetCashBoxOpenedService())->getOpenCashBox();
            if (!$aperturaCaja['status']) {
                return [
                    'estado' => 'FALLO',
                    'mensaje' => 'No hay caja aperturada para generar el documento'
                ];
            }
            $CashBoxCOrrelativeOpen = CashBoxCorrelative::where('cash_box_id', $aperturaCaja['id_caja'])->where('document_type_id', $document_type_id)->first();
            if ($CashBoxCOrrelativeOpen) {
                $document_internal_number_new = $CashBoxCOrrelativeOpen->current_number + 1;
            }
            $factura->update([
                'cashbox_open_id' => $aperturaCaja['id_apertura_caja'],
                'document_internal_number' => $document_internal_number_new,
                'operation_date' => now(),
            ]);
            $CashBoxCOrrelativeOpen->current_number = $document_internal_number_new;
            $CashBoxCOrrelativeOpen->save();
        }

        $establishmentType = trim($factura->wherehouse->stablishmenttype->code);
        $conditionCode = trim($factura->salescondition->code);
        $receptor = [
            "documentType" => trim($factura->customer->documenttypecustomer->code) ?? null,
            "documentNum" => trim($factura->customer->nit),
            "nit" => trim(str_replace("-", '', $factura->customer->nit)) ?? null,
            "nrc" => trim(str_replace("-", "", $factura->customer->nrc)) ?? null,
            "name" => trim($factura->customer->name) . " " . trim($factura->customer->last_name) ?? null,
            "phoneNumber" => ($number = preg_replace('/\D/', '', $factura->customer?->phone ?? '')) && strlen($number) >= 8 ? $number : null,
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
            $exent = !$detalle->inventory->product->is_taxed;
            $items[] = [
                "itemNum" => intval($i),
                "itemType" => 1,
                "docNum" => null,
                "code" => $codeProduc,
                "tributeCode" => null,
                "description" => $detalle->inventory->product->name . " " . $detalle->description,
                "quantity" => doubleval($detalle->quantity),
                "unit" => 1,
                "except" => $exent,
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
        $branchId = auth()->user()->employee->branch_id ?? null;
        if (!$branchId) {
            return [
                'estado' => 'FALLO',
                'mensaje' => 'No se ha configurado la empresa'
            ];
        }
        $exiteContingencia = Contingency::where('warehouse_id', $branchId)->where('is_close', 0)->first();
        $uuidContingencia = null;
        $transmissionType = 1;
        if ($exiteContingencia) {
            $uuidContingencia = $exiteContingencia->uuid_hacienda;
            $transmissionType = 2;
        }
        $sucursal = $this->warehouse($factura->wherehouse_id);

        $dte = [
            "documentType" => "03",
            "invoiceId" => intval($factura->document_internal_number),
            "transmissionType" => $transmissionType,
            "contingency" => $uuidContingencia,
            "establishmentType" => trim($establishmentType),
            "codeEstablishmentType" => trim($sucursal->establishment_type_code) ?? null,
            "codePosTerminal" => trim($sucursal->pos_terminal_code) ?? null,
            "economicAtivity" => trim($sucursal->economicactivity->code) ?? null,
            "emisorPhone" => trim($sucursal->phone) ?? null,
            "emisorAddress" => trim($sucursal->address) ?? null,
            "conditionCode" => trim($conditionCode),
            "receptor" => $receptor,
            "extencion" => $extencion,
            "items" => $items
        ];

//        return response()->json($dte);


        return $this->processDTE($dte, $idVenta);
    }

    function CreditNotesJSON($idVenta): array|JsonResponse
    {
        $factura = Sale::with('saleRelated', 'wherehouse.stablishmenttype', 'documenttype', 'seller', 'customer', 'customer.economicactivity', 'customer.departamento', 'customer.documenttypecustomer', 'salescondition', 'paymentmethod', 'saleDetails', 'saleDetails.inventory.product')->find($idVenta);
        if ($factura->document_internal_number == 0 || $factura->document_internal_number == null) {
            $document_internal_number_new = 0;
            $document_type_id = $factura->document_type_id;
            $aperturaCaja = (new GetCashBoxOpenedService())->getOpenCashBox();
            if (!$aperturaCaja['status']) {
                return [
                    'estado' => 'FALLO',
                    'mensaje' => 'No hay caja aperturada para generar el documento'
                ];
            }
            $CashBoxCOrrelativeOpen = CashBoxCorrelative::where('cash_box_id', $aperturaCaja['id_caja'])->where('document_type_id', $document_type_id)->first();
            if ($CashBoxCOrrelativeOpen) {
                $document_internal_number_new = $CashBoxCOrrelativeOpen->current_number + 1;
            }
            $factura->update([
                'cashbox_open_id' => $aperturaCaja['id_apertura_caja'],
                'document_internal_number' => $document_internal_number_new,
                'operation_date' => now(),
            ]);
            $CashBoxCOrrelativeOpen->current_number = $document_internal_number_new;
            $CashBoxCOrrelativeOpen->save();
        }
        $establishmentType = trim($factura->wherehouse->stablishmenttype->code);
        $conditionCode = 1;//trim($factura->salescondition->code);
        $receptor = [
            "documentType" => $factura->customer->documenttypecustomer->code ?? null,
            "documentNum" => $factura->customer->dui,
//            "nit" => $factura->customer->nit,
            "nit" => trim(str_replace("-", '', $factura->customer->nit)) ?? null,
            "nrc" => trim(str_replace("-", '', $factura->customer->nrc)) ?? null,
//            "nrc" => $factura->customer->nrc,
            "name" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
            "phoneNumber" => ($number = preg_replace('/\D/', '', $factura->customer?->phone ?? '')) && strlen($number) >= 8 ? $number : null,
            "email" => isset($factura->customer) ? trim($factura->customer->email ?? '') : null,
            "businessName" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
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
        $tributes = ["20"];

        foreach ($factura->saleDetails as $detalle) {
            $codeProduc = str_pad($detalle->inventory_id, 10, '0', STR_PAD_LEFT);
            $exent = !$detalle->inventory->product->is_taxed;
            $items[] = [
                "itemNum" => $i,
                "itemType" => 1,
                "docNum" => $factura->saleRelated->document_internal_number,
                "code" => $codeProduc,
                "tributeCode" => null,
                "description" => $detalle->inventory->product->name . " " . $detalle->description,
                "quantity" => doubleval($detalle->quantity),
                "unit" => 1,
                "except" => $exent,
                "unitPrice" => doubleval(number_format($detalle->price, 8, '.', '')),
                "discountPercentage" => doubleval(number_format($detalle->discount, 8, '.', '')),
                "discountAmount" => doubleval(number_format(0, 8, '.', '')),
                "exemptSale" => doubleval(number_format(0, 8, '.', '')),
                "tributes" => $tributes,
                "psv" => doubleval(number_format($detalle->price, 8, '.', '')),
                "untaxed" => doubleval(number_format(0, 8, '.', '')),
            ];
            $i++;
        }
        $branchId = auth()->user()->employee->branch_id ?? null;
        if (!$branchId) {
            return [
                'estado' => 'FALLO',
                'mensaje' => 'No hay datos de la sucursal o empresa configurados'
            ];
        }
        $exiteContingencia = Contingency::where('warehouse_id', $branchId)
            ->where('is_close', 0)->first();
        $uuidContingencia = null;
        $transmissionType = 1;
        if ($exiteContingencia) {
            $uuidContingencia = $exiteContingencia->uuid_hacienda;
            $transmissionType = 2;
        }
        $relatedDocuments[] = [
            "typeDocument" => "03",//$Nota de Credito
            "typeGeneration" => "1",//$factura->saleRelated->document_internal_number,
            "numDocument" => $factura->saleRelated->document_internal_number,
            "dateEmision" => $factura->saleRelated->operation_date,
        ];
        $sucursal = $this->warehouse($factura->wherehouse_id);

        $dte = [
            "documentType" => "05",
            "invoiceId" => intval($factura->document_internal_number),
            "establishmentType" => "02",//$establishmentType,
            "conditionCode" => $conditionCode,
            "transmissionType" => $transmissionType,
            "contingency" => $uuidContingencia,
            "relatedDocuments" => $relatedDocuments,
            "codeEstablishmentType" => trim($sucursal->establishment_type_code) ?? null,
            "codePosTerminal" => trim($sucursal->pos_terminal_code) ?? null,
            "economicAtivity" => trim($sucursal->economicactivity->code) ?? null,
            "emisorPhone" => trim($sucursal->phone) ?? null,
            "emisorAddress" => trim($sucursal->address) ?? null,
            "receptor" => $receptor,
            "extencion" => $extencion,
            "items" => $items
        ];

//        $dteJSON = json_encode($dte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
//        return response()->json($dte);

        return $this->processDTE($dte, $idVenta);
    }

    function DebitNotesJSON($idVenta): array|JsonResponse
    {
        $factura = Sale::with('saleRelated', 'wherehouse.stablishmenttype', 'documenttype', 'seller', 'customer', 'customer.economicactivity', 'customer.departamento', 'customer.documenttypecustomer', 'salescondition', 'paymentmethod', 'saleDetails', 'saleDetails.inventory.product')->find($idVenta);
        if ($factura->document_internal_number == 0 || $factura->document_internal_number == null) {
            $document_internal_number_new = 0;
            $document_type_id = $factura->document_type_id;
            $aperturaCaja = (new GetCashBoxOpenedService())->getOpenCashBox();
            if (!$aperturaCaja['status']) {
                return [
                    'estado' => 'FALLO',
                    'mensaje' => 'No hay caja aperturada para generar el documento'
                ];
            }
            $CashBoxCOrrelativeOpen = CashBoxCorrelative::where('cash_box_id', $aperturaCaja['id_caja'])->where('document_type_id', $document_type_id)->first();
            if ($CashBoxCOrrelativeOpen) {
                $document_internal_number_new = $CashBoxCOrrelativeOpen->current_number + 1;
            }
            $factura->update([
                'cashbox_open_id' => $aperturaCaja['id_apertura_caja'],
                'document_internal_number' => $document_internal_number_new,
                'operation_date' => now(),
            ]);
            $CashBoxCOrrelativeOpen->current_number = $document_internal_number_new;
            $CashBoxCOrrelativeOpen->save();
        }
        $establishmentType = trim($factura->wherehouse->stablishmenttype->code);
        $conditionCode = 1;//trim($factura->salescondition->code);
        $receptor = [
            "documentType" => $factura->customer->documenttypecustomer->code ?? null,
            "documentNum" => $factura->customer->dui,
//            "nit" => $factura->customer->nit,
            "nit" => trim(str_replace("-", '', $factura->customer->nit)) ?? null,
            "nrc" => trim(str_replace("-", '', $factura->customer->nrc)) ?? null,
//            "nrc" => $factura->customer->nrc,
            "name" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
            "phoneNumber" => ($number = preg_replace('/\D/', '', $factura->customer?->phone ?? '')) && strlen($number) >= 8 ? $number : null,
            "email" => isset($factura->customer) ? trim($factura->customer->email ?? '') : null,
            "businessName" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
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
        $tributes = ["20"];

        foreach ($factura->saleDetails as $detalle) {
            $codeProduc = str_pad($detalle->inventory_id, 10, '0', STR_PAD_LEFT);
            $exent = !$detalle->inventory->product->is_taxed;
            $items[] = [
                "itemNum" => $i,
                "itemType" => 1,
                "docNum" => $factura->saleRelated->document_internal_number,
                "code" => $codeProduc,
                "tributeCode" => null,
                "description" => $detalle->inventory->product->name . " " . $detalle->description,
                "quantity" => doubleval($detalle->quantity),
                "unit" => 1,
                "except" => $exent,
                "unitPrice" => doubleval(number_format($detalle->price, 8, '.', '')),
                "discountPercentage" => doubleval(number_format($detalle->discount, 8, '.', '')),
                "discountAmount" => doubleval(number_format(0, 8, '.', '')),
                "exemptSale" => doubleval(number_format(0, 8, '.', '')),
                "tributes" => $tributes,
                "psv" => doubleval(number_format($detalle->price, 8, '.', '')),
                "untaxed" => doubleval(number_format(0, 8, '.', '')),
            ];
            $i++;
        }
        $branchId = auth()->user()->employee->branch_id ?? null;
        if (!$branchId) {
            return [
                'estado' => 'FALLO',
                'mensaje' => 'No hay datos de la sucursal o empresa configurados'
            ];
        }
        $exiteContingencia = Contingency::where('warehouse_id', $branchId)
            ->where('is_close', 0)->first();
        $uuidContingencia = null;
        $transmissionType = 1;
        if ($exiteContingencia) {
            $uuidContingencia = $exiteContingencia->uuid_hacienda;
            $transmissionType = 2;
        }
        $relatedDocuments[] = [
            "typeDocument" => "03",//$Nota de Credito
            "typeGeneration" => "1",//$factura->saleRelated->document_internal_number,
            "numDocument" => $factura->saleRelated->document_internal_number,
            "dateEmision" => $factura->saleRelated->operation_date,
        ];
        $sucursal = $this->warehouse($factura->wherehouse_id);
        $dte = [
            "documentType" => "06",
            "invoiceId" => intval($factura->document_internal_number),
            "establishmentType" => "02",//$establishmentType,
            "conditionCode" => $conditionCode,
            "transmissionType" => $transmissionType,
            "contingency" => $uuidContingencia,
            "relatedDocuments" => $relatedDocuments,
            "codeEstablishmentType" => trim($sucursal->establishment_type_code) ?? null,
            "codePosTerminal" => trim($sucursal->pos_terminal_code) ?? null,
            "economicAtivity" => trim($sucursal->economicactivity->code) ?? null,
            "emisorPhone" => trim($sucursal->phone) ?? null,
            "emisorAddress" => trim($sucursal->address) ?? null,
            "receptor" => $receptor,
            "extencion" => $extencion,
            "items" => $items
        ];

//        $dteJSON = json_encode($dte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
//        return response()->json($dte);

        return $this->processDTE($dte, $idVenta);
    }

    function RemisionNotesJSON($idVenta): array|JsonResponse
    {
        $factura = Sale::with('saleRelated', 'wherehouse.stablishmenttype', 'documenttype', 'seller', 'customer', 'customer.economicactivity', 'customer.departamento', 'customer.documenttypecustomer', 'salescondition', 'paymentmethod', 'saleDetails', 'saleDetails.inventory.product')->find($idVenta);
        if ($factura->document_internal_number == 0 || $factura->document_internal_number == null) {
            $document_internal_number_new = 0;
            $document_type_id = $factura->document_type_id;
            $aperturaCaja = (new GetCashBoxOpenedService())->getOpenCashBox();
            if (!$aperturaCaja['status']) {
                return [
                    'estado' => 'FALLO',
                    'mensaje' => 'No hay caja aperturada para generar el documento'
                ];
            }
            $CashBoxCOrrelativeOpen = CashBoxCorrelative::where('cash_box_id', $aperturaCaja['id_caja'])->where('document_type_id', $document_type_id)->first();
            if ($CashBoxCOrrelativeOpen) {
                $document_internal_number_new = $CashBoxCOrrelativeOpen->current_number + 1;
            }
            $factura->update([
                'cashbox_open_id' => $aperturaCaja['id_apertura_caja'],
                'document_internal_number' => $document_internal_number_new,
                'operation_date' => now(),
            ]);
            $CashBoxCOrrelativeOpen->current_number = $document_internal_number_new;
            $CashBoxCOrrelativeOpen->save();
        }
        $establishmentType = trim($factura->wherehouse->stablishmenttype->code);
        $conditionCode = 1;//trim($factura->salescondition->code);
        $receptor = [
            "documentType" => $factura->customer->documenttypecustomer->code ?? null,
            "documentNum" => $factura->customer->dui,
            "nit" => trim(str_replace("-", '', $factura->customer->nit)) ?? null,
            "nrc" => null,
            "name" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
            "phoneNumber" => ($number = preg_replace('/\D/', '', $factura->customer?->phone ?? '')) && strlen($number) >= 8 ? $number : null,
            "email" => isset($factura->customer) ? trim($factura->customer->email ?? '') : null,
            "businessName" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
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
        $tributes = ["20"];

        foreach ($factura->saleDetails as $detalle) {
            $codeProduc = str_pad($detalle->inventory_id, 10, '0', STR_PAD_LEFT);
            $exent = !$detalle->inventory->product->is_taxed;
            $items[] = [
                "itemNum" => $i,
                "itemType" => 1,
                "docNum" => $factura->saleRelated->document_internal_number ?? null,
                "code" => $codeProduc,
                "tributeCode" => null,
                "description" => $detalle->inventory->product->name . " " . $detalle->description,
                "quantity" => doubleval($detalle->quantity),
                "unit" => 1,
                "except" => $exent,
                "unitPrice" => doubleval(number_format($detalle->price, 8, '.', '')),
                "discountPercentage" => doubleval(number_format($detalle->discount, 8, '.', '')),
                "discountAmount" => doubleval(number_format(0, 8, '.', '')),
                "exemptSale" => doubleval(number_format(0, 8, '.', '')),
                "tributes" => $tributes,
                "psv" => doubleval(number_format($detalle->price, 8, '.', '')),
                "untaxed" => doubleval(number_format(0, 8, '.', '')),
            ];
            $i++;
        }
        $branchId = auth()->user()->employee->branch_id ?? null;
        if (!$branchId) {
            return [
                'estado' => 'FALLO',
                'mensaje' => 'No hay datos de la sucursal o empresa configurados'
            ];
        }
        $exiteContingencia = Contingency::where('warehouse_id', $branchId)
            ->where('is_close', 0)->first();
        $uuidContingencia = null;
        $transmissionType = 1;
        if ($exiteContingencia) {
            $uuidContingencia = $exiteContingencia->uuid_hacienda;
            $transmissionType = 2;
        }
        $relatedDocuments[] = [
            "typeDocument" => "03",//CCF
            "typeGeneration" => "1",//$factura->saleRelated->document_internal_number,
            "numDocument" => $factura->saleRelated->document_internal_number ?? null,
            "dateEmision" => $factura->saleRelated->operation_date ?? null,
        ];
        $sucursal = $this->warehouse($factura->wherehouse_id);
        $dte = [
            "documentType" => "04",
            "invoiceId" => intval($factura->document_internal_number),
            "establishmentType" => "02",//$establishmentType,
            "conditionCode" => $conditionCode,
            "transmissionType" => $transmissionType,
            "contingency" => $uuidContingencia,
            "relatedDocuments" => $relatedDocuments,
            "codeEstablishmentType" => trim($sucursal->establishment_type_code) ?? null,
            "codePosTerminal" => trim($sucursal->pos_terminal_code) ?? null,
            "economicAtivity" => trim($sucursal->economicactivity->code) ?? null,
            "emisorPhone" => trim($sucursal->phone) ?? null,
            "emisorAddress" => trim($sucursal->address) ?? null,
            "receptor" => $receptor,
            "extencion" => $extencion,
            "items" => $items,
            "assetTitle" => $receptor['name'] ?? 'SN'
        ];

//        $dteJSON = json_encode($dte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
//        return response()->json($dte);

        return $this->processDTE($dte, $idVenta);
    }

    function ExportacionJson($idVenta): array|jsonResponse
    {
        $factura = Sale::with('wherehouse.stablishmenttype', 'documenttype', 'seller', 'customer', 'customer.economicactivity', 'customer.departamento', 'customer.documenttypecustomer', 'salescondition', 'paymentmethod', 'saleDetails', 'saleDetails.inventory.product')->find($idVenta);
        if ($factura->document_internal_number == 0 || $factura->document_internal_number == null) {
            $document_internal_number_new = 0;
            $document_type_id = $factura->document_type_id;
            $aperturaCaja = (new GetCashBoxOpenedService())->getOpenCashBox();
            if (!$aperturaCaja['status']) {
                return [
                    'estado' => 'FALLO',
                    'mensaje' => 'No hay caja aperturada para generar el documento'
                ];
            }
            $CashBoxCOrrelativeOpen = CashBoxCorrelative::where('cash_box_id', $aperturaCaja['id_caja'])->where('document_type_id', $document_type_id)->first();
            if ($CashBoxCOrrelativeOpen) {
                $document_internal_number_new = $CashBoxCOrrelativeOpen->current_number + 1;
            }
            $factura->update([
                'cashbox_open_id' => $aperturaCaja['id_apertura_caja'],
                'document_internal_number' => $document_internal_number_new,
                'operation_date' => now(),
            ]);
            $CashBoxCOrrelativeOpen->current_number = $document_internal_number_new;
            $CashBoxCOrrelativeOpen->save();
        }
        $establishmentType = trim($factura->wherehouse->stablishmenttype->code);
        $conditionCode = 1;//trim($factura->salescondition->code);
        $receptor = [
//            "documentType" => $factura->customer->documenttypecustomer->code ?? null,
//            "documentNum" => $factura->customer->dui ?? $factura->customer->nit,
//            "name" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
//            "nit" => null,
//            "nrc" => null,
//            "phoneNumber" => isset($factura->customer) ? str_replace(["(", ")", "-", " "], "", $factura->customer->phone ?? '') : null,
//            "email" => isset($factura->customer) ? trim($factura->customer->email ?? '') : null,
//            "businessName" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
//            "address" => isset($factura->customer) ? trim($factura->customer->address ?? '') : null,
//            "codeMunicipality" => null,// isset($factura->customer->distrito) ? trim($factura->customer->distrito->code ?? '') : null,
//            "codCountry" => "9450",
//            "personType" => 1
            "documentType" => $factura->customer->documenttypecustomer->code ?? 37,
            "documentNum" => $factura->customer->dui,
            "nit" => null,
            "nrc" => null,
            "name" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
//            "phoneNumber" => isset($factura->customer) ? str_replace(["(", ")", "-", " "], "", $factura->customer->phone ?? '') : null,
            "phoneNumber" => ($number = preg_replace('/\D/', '', $factura->customer?->phone ?? '')) && strlen($number) >= 8 ? $number : null,

            "email" => isset($factura->customer) ? trim($factura->customer->email ?? '') : null,
            "businessName" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
            "address" => isset($factura->customer) ? trim($factura->customer->address ?? '') : null,
            "economicAtivity" => null,
            "codeCity" => null,
            "codeMunicipality" => null,
            "codCountry" => $factura->customer->country->code ?? null,
            "personType" => 1
//  },
        ];
        $extencion = ["deliveryName" => isset($factura->seller) ? trim($factura->seller->name ?? '') . " " . trim($factura->seller->last_name ?? '') : null,
            "deliveryDoc" => isset($factura->seller) ? str_replace("-", "", $factura->seller->dui ?? '') : null,];
        $items = [];
        $i = 1;

        foreach ($factura->saleDetails as $detalle) {
            $codeProduc = str_pad($detalle->inventory_id, 10, '0', STR_PAD_LEFT);
            $exent = !$detalle->inventory->product->is_taxed;
            $items[] = ["itemNum" => $i,
                "itemType" => 0,
                "docNum" => "",
                "code" => $codeProduc,
//                "tributeCode" => null,
                "description" => $detalle->inventory->product->name . " " . $detalle->description,
                "quantity" => doubleval($detalle->quantity),
                "unit" => 1,
                "except" => $exent,
                "unitPrice" => doubleval(number_format($detalle->price, 8, '.', '')),
                "discount" => 0,
                "discountPercentage" => doubleval(number_format($detalle->discount, 8, '.', '')),
                "discountAmount" => doubleval(number_format(0, 8, '.', '')),
                "exemptSale" => doubleval(number_format(0, 8, '.', '')),
                "tributes" => null,
                "psv" => 0,// doubleval(number_format($detalle->price, 8, '.', '')),
                "untaxed" => doubleval(number_format(0, 8, '.', '')),];
            $i++;
        }

        $branchId = auth()->user()->employee->branch_id ?? null;
        if (!$branchId) {
            return [
                'estado' => 'FALLO',
                'mensaje' => 'No hay datos de la sucursal o empresa configurados'
            ];
        }
        $exiteContingencia = Contingency::where('warehouse_id', $branchId)
            ->where('is_close', 0)->first();
        $uuidContingencia = null;
        $transmissionType = 1;
        if ($exiteContingencia) {
            $uuidContingencia = $exiteContingencia->uuid_hacienda;
            $transmissionType = 2;
        }
        $sucursal = $this->warehouse($factura->wherehouse_id);
        $dte = [
            "documentType" => "11",//Factura de exportacion
            "invoiceId" => intval($factura->document_internal_number),
            "establishmentType" => "02",//;$establishmentType,
            "conditionCode" => $conditionCode,
            "transmissionType" => $transmissionType,
            "contingency" => $uuidContingencia,
            "codeEstablishmentType" => trim($sucursal->establishment_type_code) ?? null,
            "codePosTerminal" => trim($sucursal->pos_terminal_code) ?? null,
            "economicAtivity" => trim($sucursal->economicactivity->code) ?? null,
            "emisorPhone" => trim($sucursal->phone) ?? null,
            "emisorAddress" => trim($sucursal->address) ?? null,
            "receptor" => $receptor,
            "extencion" => $extencion,
            "items" => $items,
            "fiscalPrecinct" => null,
            "regimen" => null,
            "itemExportype" => 2,
            "incoterms" => null,
            "description" => "string",
            "freight" => 0,
            "insurance" => 0,
            "relatedDocuments" => null,
        ];

//        $dteJSON = json_encode($dte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
//        return response()->json($dte);

        return $this->processDTE($dte, $idVenta);
    }

    function sujetoExcluidoJson($idVenta): array|jsonResponse
    {
        $factura = Sale::with('wherehouse.stablishmenttype', 'documenttype', 'seller', 'customer', 'customer.economicactivity', 'customer.departamento', 'customer.documenttypecustomer', 'salescondition', 'paymentmethod', 'saleDetails', 'saleDetails.inventory.product')->find($idVenta);
      if ($factura->document_internal_number == 0 || $factura->document_internal_number == null) {
            $document_internal_number_new = 0;
            $document_type_id = $factura->document_type_id;
            $aperturaCaja = (new GetCashBoxOpenedService())->getOpenCashBox();
          if (!$aperturaCaja['status']) {
                return [
                    'estado' => 'FALLO',
                    'mensaje' => 'No hay caja aperturada para generar el documento'
                ];
            }
            $CashBoxCOrrelativeOpen = CashBoxCorrelative::where('cash_box_id', $aperturaCaja['id_caja'])->where('document_type_id', $document_type_id)->first();
            if ($CashBoxCOrrelativeOpen) {
                $document_internal_number_new = $CashBoxCOrrelativeOpen->current_number + 1;
            }
            $factura->update([
                'cashbox_open_id' => $aperturaCaja['id_apertura_caja'],
                'document_internal_number' => $document_internal_number_new,
                'operation_date' => now(),
            ]);
            $CashBoxCOrrelativeOpen->current_number = $document_internal_number_new;
            $CashBoxCOrrelativeOpen->save();
        }
        $establishmentType = trim($factura->wherehouse->stablishmenttype->code);
        $conditionCode = (int)trim($factura->salescondition->code ?? 1);

        $receptor = [
            "documentType" => $factura->customer->documenttypecustomer->code ?? null,
            "documentNum" => isset($factura->customer) ? str_replace(["(", ")", "-", " "], "", $factura->customer->nit ?? '') : null,
            "nit" => $factura->customer->nit ?? null,
            "nrc" => $factura->customer->nrc ?? null,
            "name" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
            "phoneNumber" => ($number = preg_replace('/\D/', '', $factura->customer?->phone ?? '')) && strlen($number) >= 8 ? $number : null,
            "email" => isset($factura->customer) ? trim($factura->customer->email ?? '') : null,
            "businessName" => isset($factura->customer) ? trim($factura->customer->name ?? '') . " " . trim($factura->customer->last_name ?? '') : null,
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
            $exent = !$detalle->inventory->product->is_taxed;
            $items[] = [
                "itemNum" => $i,
                "itemType" => 1,
                "docNum" => null,
                "code" => $codeProduc,
                "tributeCode" => null,
                "description" => $detalle->inventory->product->name . " " . $detalle->description,
                "quantity" => doubleval($detalle->quantity),
                "unit" => 1,
                "except" => $exent,
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
            return [
                'estado' => 'FALLO',
                'mensaje' => 'No hay datos de la sucursal o empresa configurados'
            ];
        }
        $exiteContingencia = Contingency::where('warehouse_id', $branchId)
            ->where('is_close', 0)->first();
        $uuidContingencia = null;
        $transmissionType = 1;
        if ($exiteContingencia) {
            $uuidContingencia = $exiteContingencia->uuid_hacienda;
            $transmissionType = 2;
        }
        $dte = [
            "documentType" => "14",
            "invoiceId" => intval($factura->document_internal_number),
            "establishmentType" => $establishmentType,
            "conditionCode" => $conditionCode,
//            "transmissionType" => $transmissionType,
            "contingency" => $uuidContingencia,
            "receptor" => $receptor,
            "extencion" => $extencion,
            "items" => $items,
            "fiscalPrecinct" => null,
            "regimen" => null,
            "ItemExportype" => 3,
            "incoterms" => null,
            "description" => null,
            "freight" => null,
            "insurance" => null,
            "relatedDocuments" => null,
            "transmissionType" => 1
        ];

//        $dteJSON = json_encode($dte, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
//return response()->json($dte);

        return $this->processDTE($dte, $idVenta);
    }

    function SendDTE($dteData, $idVenta): array|JsonResponse // Assuming $dteData is the data you need to send
    {
        set_time_limit(0);
        try {
//            echo env(DTE_TEST);
            $urlAPI = env('DTE_URL') . '/api/DTE/generateDTE'; // Set the correct API URL
            $apiKey = trim($this->getConfiguracion()->api_key); // Assuming you retrieve the API key from your config
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
            curl_close($curl);

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

//            dd($response);
//            return response()->json($response);

            $responseData = json_decode($response, true);


            //validar si respuesta hacienda es null pero tiene firma, si es asi es contingencia


            $responseHacienda = (isset($responseData["estado"]) == "RECHAZADO") ? $responseData : $responseData["respuestaHacienda"];
//            $responseHacienda = isset($responseData["estado"]) && $responseData["estado"] === "RECHAZADO"
//                ? $responseData
//                : ($responseData["respuestaHacienda"] ?? $responseData["identificacion"] ?? null);
//            $responseData["estado"] = "procesado"; // Reemplaza con el valor deseado
//            if (isset($responseHacienda["valueKind"]) && $responseHacienda["valueKind"] == 1) {
//                $responseData["estado"] = "EXITO";
//            }


            $falloDTE = new HistoryDte;
            $ventaID = intval($idVenta);
            $falloDTE->sales_invoice_id = $ventaID;
            $falloDTE->version = $responseHacienda["version"] ?? 0;
            $falloDTE->ambiente = $responseHacienda["ambiente"] ?? 0;
            $falloDTE->versionApp = $responseHacienda["versionApp"] ?? 0;
            $falloDTE->estado = $responseHacienda["estado"] ?? null;
            $falloDTE->codigoGeneracion = $responseHacienda["codigoGeneracion"] ?? null;
            $falloDTE->contingencia = $responseHacienda["tipoContingencia"] ?? null;
            $falloDTE->motivo_contingencia = $responseHacienda["motivoContin"] ?? null;
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
            $falloDTE->observaciones = isset($responseHacienda["observaciones"]) ? json_encode($responseHacienda["observaciones"]) : (isset($responseHacienda["descripcion"]) ? json_encode($responseHacienda["descripcion"]) : null);

            $falloDTE->dte = $responseData ?? null;
            $falloDTE->save();


            return $responseData;

        } catch (Exception $e) {
            $data = [
                'estado' => 'RECHAZADO',
                'mensaje' => "Ocurrio un eror " . $e
            ];
            return $data;
        }
    }


    public
    function anularDTE($idVenta): array|JsonResponse
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
        $user = Auth::user()->employee;
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
//        return response()->json($responseData);
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
            //regresar el inventario
            $excluded = [4, 6];//[4]-NOta de debito y [6]-NOta de Remision
            if (!in_array($venta->document_type_id, $excluded)) {
                $salesItem = SaleItem::where('sale_id', $venta->id)->get();
                $client = $venta->customer;
                $documnetType = $venta->documenttype->name ?? 'S/N';
//                    $entity = $client->name??'' . ' ' . $client->last_name??'';
                $entity = ($client->name ?? 'Varios') . ' ' . ($client->last_name ?? '');

                $pais = $client->country->name ?? 'Salvadoreña';
                foreach ($salesItem as $item) {
                    $inventory = Inventory::with('product')->find($item->inventory_id);
                    // Verifica si el inventario existe
                    if (!$inventory) {
                        Log::error("Inventario no encontrado para el item de compra: {$item->id}");
                        continue; // Si no se encuentra el inventario, continua con el siguiente item
                    }
                    // Actualiza el stock del inventario

                    //verificar si es un producto compuesto
                    $is_grouped = $inventory->product->is_grouped;
                    if ($is_grouped) {
                        //si es compuesto traemos todos los inventario que lo componen
                        $inventoriesGrouped = InventoryGrouped::with('inventoryChild.product')->where('inventory_grouped_id', $item->inventory_id)->get();

                        foreach ($inventoriesGrouped as $inventarioHijo) {
                            $newStock = $inventarioHijo->stock + $item->quantity;
                            $inventarioHijo->update(['stock' => $newStock]);
                            $kardex = KardexHelper::createKardexFromInventory(
                                $inventarioHijo->inventoryChild->branch_id, // Se pasa solo el valor de branch_id (entero)
                                now(), // date
                                'Anulacion Venta', // operation_type
                                $venta->id, // operation_id
                                $item->id, // operation_detail_id
                                $documnetType, // document_type
                                $venta->document_internal_number, // document_number
                                $entity, // entity
                                $pais, // nationality
                                $inventarioHijo->inventory_child_id, // inventory_id
                                $inventarioHijo->inventoryChild->stock ?? 0 - $inventarioHijo->quantity ?? 0, // previous_stock
                                $inventarioHijo->quantity, // stock_in
                                0, // stock_out
                                $inventarioHijo->inventoryChild->stock ?? 0 - $inventarioHijo->quantity ?? 0, // stock_actual
                                $inventarioHijo->quantity ?? 0 * $inventarioHijo->sale_price ?? 0, // money_in
                                0, // money_out
                                $inventarioHijo->inventoryChild->stock ?? 0 * $inventarioHijo->sale_price ?? 0, // money_actual
                                $inventarioHijo->sale_price ?? 0, // sale_price
                                $inventarioHijo->inventoryChild->cost_without_taxes ?? 0 // purchase_price
                            );
                        }
                    } else {
                        $newStock = $inventory->stock + $item->quantity;
                        $inventory->update(['stock' => $newStock]);
                        $kardex = KardexHelper::createKardexFromInventory(
                            $inventory->branch_id, // Se pasa solo el valor de branch_id (entero)
                            now(), // date
                            'Anulacion Venta', // operation_type
                            $venta->id, // operation_id
                            $item->id, // operation_detail_id
                            $documnetType, // document_type
                            $venta->document_internal_number, // document_number
                            $entity, // entity
                            $pais, // nationality
                            $inventory->id, // inventory_id
                            $inventory->stock - $item->quantity, // previous_stock
                            $item->quantity, // stock_in
                            0, // stock_out
                            $newStock, // stock_actual
                            $item->quantity * $item->price, // money_in
                            0, // money_out
                            $inventory->stock * $item->price, // money_actual
                            $item->price, // sale_price
                            $inventory->cost_without_taxes // purchase_price
                        );
                    }


                    // Verifica si la creación del Kardex fue exitosa
                    if (!$kardex) {
                        Log::error("Error al crear Kardex para el item de compra: {$item->id}");
                    }
                }
            }

            return [
                'estado' => 'EXITO',
                'mensaje' => 'DTE ANULADO correctamente',
            ];
        }
    }

    function SendAnularDTE($dteData, $idVenta) // Assuming $dteData is the data you need to send
    {
        try {
            $urlAPI = env('DTE_URL') . '/api/DTE/cancellationDTE'; // Set the correct API URL
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

            // Check for cURL errors
            if ($response === false) {
                return [
                    'estado' => 'RECHAZADO ',
                    'mensaje' => "Ocurrio un eror" . curl_error($curl)
                ];
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

    public
    function printDTETicket($codGeneracion)
    {
        // Leer directamente de la base de datos
        $historyDte = HistoryDte::where('codigoGeneracion', $codGeneracion)->first();

        if (!$historyDte || !$historyDte->dte) {
            return [
                'estado' => 'Error',
                'mensaje' => 'El DTE no existe en la base de datos',
            ];
        }

        $DTE = is_array($historyDte->dte) ? $historyDte->dte : json_decode($historyDte->dte, true);
        $tipoDocumento = $DTE['identificacion']['tipoDte'] ?? 'DESCONOCIDO';
        $logo = auth()->user()->employee->wherehouse->logo;
        $tiposDTE = [
            '03' => 'COMPROBANTE DE CREDITO  FISCAL',
            '01' => 'FACTURA',
            '04' => 'NOTA DE REMISION',
            '05' => 'NOTA DE CREDITO',
            '06' => 'NOTA DE DEBITO',
            '07' => 'COMPROBANTE DE RETENCION',
            '08' => 'COMPROBANTE DE LIQUIDACION',
            '11' => 'FACTURA DE EXPORTACION',
            '14' => 'SUJETO EXCLUIDO'
        ];
        $tipoDocumento = $this->searchInArray($tipoDocumento, $tiposDTE);
        $contenidoQR = "https://admin.factura.gob.sv/consultaPublica?ambiente=" . env('DTE_AMBIENTE_QR') . "&codGen=" . $DTE['identificacion']['codigoGeneracion'] . "&fechaEmi=" . $DTE['identificacion']['fecEmi'];

        // Crear QR en carpeta temporal
        $directory = storage_path('app/temp/QR');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $qrPath = $directory . '/' . $codGeneracion . '.jpg';
        QrCode::size(300)->generate($contenidoQR, $qrPath);

        if (!file_exists($qrPath)) {
            throw new Exception("Error: El archivo QR no fue guardado correctamente");
        }

        // Convertir imágenes a base64 para DomPDF (más confiable)
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
            'tipoDocumento' => $tipoDocumento,
            'logo' => $logoBase64,
        ];

        $qr = $qrBase64;

        $pdf = Pdf::loadView('DTE.dte-print-ticket', compact('datos', 'qr'))
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        $pdf->set_paper(array(0, 0, 250, 1000));

        // Renderizar PDF y limpiar QR temporal
        $output = $pdf->output();
        @unlink($qrPath);

        return response($output)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $codGeneracion . '.pdf"');
    }

    public
    function printDTEPdf($codGeneracion)
    {
        // Leer directamente de la base de datos
        $historyDte = HistoryDte::where('codigoGeneracion', $codGeneracion)->first();

        if (!$historyDte || !$historyDte->dte) {
            return [
                'estado' => 'Error',
                'mensaje' => 'El DTE no existe en la base de datos',
            ];
        }

        $DTE = is_array($historyDte->dte) ? $historyDte->dte : json_decode($historyDte->dte, true);
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

        // Crear QR en carpeta temporal
        $directory = storage_path('app/temp/QR');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        $qrPath = $directory . '/' . $codGeneracion . '.jpg';
        QrCode::size(300)->generate($contenidoQR, $qrPath);

        if (!file_exists($qrPath)) {
            throw new Exception("Error: El archivo QR no fue guardado correctamente");
        }

        // Convertir imágenes a base64 para DomPDF (más confiable)
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
            'tipoDocumento' => $tipoDocumento,
            'logo' => $logoBase64,
        ];

        $qr = $qrBase64;

        $pdf = Pdf::loadView('DTE.dte-print-pdf', compact('datos', 'qr'))
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
            ]);

        // Renderizar PDF y limpiar QR temporal
        $output = $pdf->output();
        @unlink($qrPath);

        return response($output)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $codGeneracion . '.pdf"');
    }

    public
    function getDTE($codGeneracion)
    {
        set_time_limit(0);
        try {
//            echo env(DTE_TEST);
//            $urlAPI = env('DTE_URL_REPORT') .'/api/DTE/json/'.$codGeneracion; // Set the correct API URL
//            $apiKey = trim($this->getConfiguracion()->api_key); // Assuming you retrieve the API key from your config
//            $curl = curl_init();
//            curl_setopt_array($curl, array(
//                CURLOPT_URL => $urlAPI,
//                CURLOPT_RETURNTRANSFER => true,
//                CURLOPT_ENCODING => '',
//                CURLOPT_MAXREDIRS => 10,
//                CURLOPT_TIMEOUT => 0,
//                CURLOPT_FOLLOWLOCATION => true,
//                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                CURLOPT_CUSTOMREQUEST => 'GET',
//                CURLOPT_HTTPHEADER => array(
//                    'Content-Type: application/json',
//                    'apiKey: ' . $apiKey
//                ),
//            ));
//
//            $response = curl_exec($curl);
//            curl_close($curl);
//
//            $response = curl_exec($curl);
//            curl_close($curl);

            $history = HistoryDte::where('codigoGeneracion', '=', $codGeneracion)->first();


            $responseData = json_decode($history, true);
            return response()->json($responseData);


        } catch (Exception $e) {
            $data = [
                'estado' => 'RECHAZADO',
                'mensaje' => "Ocurrio un eror " . $e
            ];
            return $data;
        }


    }

    /**
     * @param mixed $responseData
     * @param $idVenta
     * @return void
     */
    public
    function saveJson(mixed $responseData, $idVenta, $enviado_hacienda): void
    {
        $codGeneration = $responseData['respuestaHacienda']['codigoGeneracion'] ?? $responseData['identificacion']['codigoGeneracion'] ?? null;

        // Ya no guardamos archivo físico - el JSON está en history_dtes.dte
        // Solo actualizamos la venta con el código de generación
        $venta = Sale::find($idVenta);
        $venta->is_dte = true;
        $venta->is_hacienda_send = $enviado_hacienda;
        $venta->generationCode = $codGeneration ?? null;
        $venta->jsonUrl = null; // Ya no usamos archivo físico
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

    /**
     * @param array $dte
     * @param $idVenta
     * @return array
     */
    public
    function processDTE(array $dte, $idVenta): array|jsonResponse
    {

        $responseData = $this->SendDTE($dte, $idVenta);
//    dd($responseData['respuestaHacienda']['estado']);
//    if (isset($responseData['respuestaHacienda']['estado']) && $responseData['respuestaHacienda']["estado"] === "RECHAZADO" || $responseData["estado"] === "RECHAZADO") {
        if (
            (isset($responseData['respuestaHacienda']['estado']) && $responseData['respuestaHacienda']['estado'] === "RECHAZADO")
            || (isset($responseData["estado"]) && $responseData["estado"] === "RECHAZADO")
        ) {

            return [
                'estado' => 'FALLO', // o 'ERROR'
                'response' => $responseData,
                'mensaje' => 'DTE falló al enviarse: ' . implode(', ', $responseData['descripcionMsg'] ?? []), // Concatenar observaciones
            ];
        } else if (isset($responseData['respuestaHacienda']["estado"]) && $responseData['respuestaHacienda']["estado"] == "PENDIENTE") {
            $this->saveJson($responseData, $idVenta, false);
            return [
                'estado' => 'CONTINGENCIA',
                'mensaje' => 'DTE procesado correctamente - Pendiente envio a hacienda',
            ];
        } else if (isset($responseData['respuestaHacienda']["estado"]) && $responseData['respuestaHacienda']["estado"] == "PROCESADO") {
            $this->saveJson($responseData, $idVenta, true);
            return [
                'estado' => 'EXITO',
                'mensaje' => 'DTE enviado correctamente',
            ];
        } else {
//            $this->saveJson($responseData, $idVenta, false);
            return [
                'estado' => 'FALLO',
                'mensaje' => 'Error desconocido' // Concatenar observaciones
            ];
        }
    }
}
