<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contingency;
use App\Models\HistoryDte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContingencyController extends Controller
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

    public function contingencyDTE($motivo):array|jsonResponse|string|bool
    {
        set_time_limit(0);
        try {
            $urlAPI = env('DTE_URL') .'/api/Contingency/DTE'; // Set the correct API URL
            $apiKey = $this->getConfiguracion()->api_key; // Assuming you retrieve the API key from your config

            $dteData = [
                'description' => $motivo,
            ];
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
            $data = json_decode($response, true); // Convertir JSON a array
//            return response()->json($data['data']);


            $contingency = new Contingency();
            $contingency->warehouse_id = \Auth::user()->employee->branch_id ;
            $contingency->uuid_hacienda = $data['data']['uuid'];
            $contingency->start_date = $data['data']['inicioContingencia'];
            $contingency->contingency_types_id = $data['data']['tipoContiengencia'];
            $contingency->contingency_motivation = $data['data']['motivo'];
            $contingency->is_close=false;
            if($contingency->save()){
                return true;
            }else{
                return false;
            }
        }catch (\Exception $e){
        return $e->getMessage();
        }
    }
    public function contingencyCloseDTE($uuid_contingence): true|JsonResponse|string
    {
        set_time_limit(0);
        try {
            $urlAPI = env('DTE_URL') .'/api/Contingency/Close'; // Set the correct API URL
            $apiKey = $this->getConfiguracion()->api_key; // Assuming you retrieve the API key from your config

            $dteData = [
                'uuid' => $uuid_contingence,
            ];
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
            $data = json_decode($response, true); // Convertir JSON a array
            $contingency=Contingency::where('uuid_hacienda',$uuid_contingence)->first();
            $contingency->end_date = $data['fhProcesamiento']??null;
            $contingency->is_close=true;

            if($contingency->save()){
                return true;
            }else{
                return false;
            }
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }


}
