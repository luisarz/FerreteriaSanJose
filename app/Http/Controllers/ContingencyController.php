<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contingency;
use App\Models\HistoryDte;
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

    public function contingencyDTE($motivo)
    {
        set_time_limit(0);
        try {
            $urlAPI = 'http://api-fel-sv-dev.olintech.com/api/Contingency/DTE'; // Set the correct API URL
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
            $contingency = new Contingency();
            $contingency->warehouse_id = \Auth::user()->employee->branch_id ;
            $contingency->uuid_hacienda = $data['data']['UUID'];
            $contingency->start_date = $data['data']['InicioContingencia'];
            $contingency->contingency_types_id = $data['data']['TipoContiengencia'];
            $contingency->contingency_motivation = $data['data']['Motivo'];
            $contingency->is_close=false;
            if($contingency->save()){
                return true;
            }
            return response()->json($response);
        }catch (\Exception $e){
        return $e->getMessage();
        }
    }
    public function contingencyCloseDTE($uuid_contingence)
    {
        set_time_limit(0);
        try {
            $urlAPI = 'http://api-fel-sv-dev.olintech.com/api/Contingency/Close'; // Set the correct API URL
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
//            dd($data);
            $contingency=Contingency::where('uuid_hacienda',$uuid_contingence)->first();
            $contingency->end_date = $data['FinContingencia'];
            $contingency->is_close=true;

            if($contingency->save()){
                return true;
            }
            return response()->json($response);
        }catch (\Exception $e){
            return $e->getMessage();
        }
    }


}
