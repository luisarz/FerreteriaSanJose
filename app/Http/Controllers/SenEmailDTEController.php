<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Support\Facades\Mail;
use App\Mail\sendEmailDTE as sendDTEFiles;

class SenEmailDTEController extends Controller
{
    public function SenEmailDTEController($idVenta): \Illuminate\Http\JsonResponse
    {
        $sale = Sale::with('customer','wherehouse','wherehouse.company')->find($idVenta);
        if (!$sale) {
            return response()->json([
                'status' => false,
                'message' => 'Venta no encontrada',
            ]);
        }

        $generationCode = $sale->generationCode;

        // Generar rutas de archivos
        $JsonPath = storage_path('app/public/DTEs/' . $generationCode . '.json');
        $PdfPath = storage_path('app/public/DTEs/' . $generationCode . '.pdf');


        // Datos de respuesta por defecto
        $data = [
            'status' => false,
            'message' => 'No se encontraron los archivos' .$generationCode,
            'body' => 'Por favor gnere el PDF del cliente antes de enviar el correo',
        ];
        if (file_exists($JsonPath) && file_exists($PdfPath)) {
            try {
                Mail::to($sale->customer->email)
                    ->send(new sendDTEFiles($JsonPath, $PdfPath, $sale));
                $data = [
                    'status' => true,
                    'message' => 'Email enviado exitosamente',
                    'body' => 'Correo enviado a ' . $sale->customer->email,

                ];
            } catch (\Exception $e) {
                $data = [
                    'status' => false,
                    'message' => 'Error al enviar el correo: ' . $e->getMessage(),
                    'body' => 'Error al enviar el correo a ' . $sale->customer->email,
                ];
            }
        }

        return response()->json($data);
    }
}
