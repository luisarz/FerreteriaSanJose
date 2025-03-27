<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Tributario Electrónico</title>
    <style>
        body {
            font-family:  Verdana, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            display: flex;
            text-align: center;
            flex-direction: column;
            min-height: 100vh;
        }

        .header {
            width: 100%;
            /*text-align: center;*/
        }

        .footer {
            width: 100%;
            text-align: right;
            font-size: 12px;
        }

        .content {
            flex: 1;
        }

        .header img {
            width: 100px;
        }

        .empresa-info, .documento-info, .tabla-productos, .resumen {
            margin: 10px 0;
        }

        .tabla-productos th, .tabla-productos td {
            /*padding: 5px;*/
        }

        .tabla-productos th {
            /*background-color: #f2f2f2;*/
        }

        .resumen p {
            margin: 5px 0;
            text-align: right;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }
    </style>
</head>
<body>
<!-- Header Empresa -->
<div class="header">
    <table style="width: 100%; padding: 0; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; text-align: left; vertical-align: middle; padding-right: 10px;">

                <img src="{{ asset($datos['logo']) }}" alt="Logo de la empresa" style="width: 150px; height: auto;">

            </td>


            <td style="width: 50%; text-align: right; vertical-align: middle; padding-left: 10px;">
                <img src="{{ asset($qr) }}" alt="QR Código"
                     style="max-width: 150px; height: auto; margin-left: auto;">
            </td>
        </tr>
    </table>



</div>
<div class="header">
    <table style="text-align: left; border: black solid 0px; border-radius: 10px;">
        <tr>

            <td style="text-align: left;">
                <h4>{{ $datos['empresa']['nombre'] }}</h4>
                NIT: {{ $datos['empresa']['nit'] }}<br>
                NRC: {{ $datos['empresa']['nrc'] }}
            </td>
        </tr>
        <tr>
            <td colspan="2" style="font-size: 12px;">
                {{ $datos['empresa']['descActividad'] }}<br>
                {{ $datos['empresa']['direccion']['complemento'] }}<br>
                Teléfono: {{ $datos['empresa']['telefono'] }}
            </td>
        </tr>
    </table>
    <div style="text-align: left;">Vendedor: {{ $datos['DTE']['extencion']['nombEntrega']??'S/N' }}</div>
    ---------------------------------------------------------------------------
    <div style="text-align: left">
        <h4>DOCUMENTO TRIBUTARIO ELECTRÓNICO</h4>
        <h5>{{ $datos['tipoDocumento'] }}</h5>
        <b>Código de generación</b> <br>
        {{ $datos['DTE']['respuestaHacienda']['codigoGeneracion'] ?? $datos['DTE']['identificacion']['codigoGeneracion'] }}

        <br>
        <br>
        <b>Número de control</b> <br>
        {{ $datos['DTE']['identificacion']['numeroControl'] }} <br>
        <b>Sello de recepción:</b> <br>
        {{ $datos['DTE']['respuestaHacienda']['selloRecibido']?? 'CONTINGENCIA' }} <br>
        <b>Fecha emisión</b> <br>
        {{ date('d/m/Y', strtotime($datos['DTE']['identificacion']['fecEmi'])) }} {{ $datos['DTE']['identificacion']['horEmi'] }}
    </div>
    ---------------------------------------------------------------------------

</div>
<?php
//$url = "http://api-fel-sv-dev.olintech.com/api/Catalog/municipalities/12"; // Endpoint
//$response = file_get_contents($url);
//print_r($response);
//$data = json_decode($response, true); // Decodificar JSON a un array asociativo
//
//print_r($data); // Muestra la respuesta
?>
        <!-- Contenido principal -->

<div class="content">
    <!-- Info Cliente -->

    <div class="cliente-info">
        <table>
            <tr>
                <td>
                    <p>Razón Social: {{ $datos['DTE']['receptor']['nombre'] }}<br>
                        Documento: {{ $datos['DTE']['receptor']['numDocumento'] ?? '' }}<br>
                        Actividad: {{ $datos['DTE']['receptor']['codActividad'] }}
                        - {{ $datos['DTE']['receptor']['descActividad'] }}<br>
                        {{--                        Dirección: {{ $datos['DTE']['receptor']['direccion']['complemento'] }}<br>--}}
                        Teléfono: {{ $datos['DTE']['receptor']['telefono'] }} <br>
                        Correo: {{ $datos['DTE']['receptor']['correo'] }}
                    </p>
                </td>

            </tr>
        </table>
    </div>
    ---------------------------------------------------------------------------
    <!-- Tabla Productos -->
    <table class="tabla-productos" width="100%" border="0" cellspacing="0" cellpadding="5">

        <tbody>
        @foreach ($datos['DTE']['cuerpo'] as $item)
            <tr>
                <td>{{ $item['cantidad'] }}</td>
                <td colspan="2">{{ $item['descripcion'] }}</td>

            </tr>
            <tr>
                <td></td>
                <td>${{ number_format($item['precioUni'], 2) }}</td>
                <td>Desc. ${{ number_format($item['montoDescu'], 2) }}</td>
                <td>${{ number_format($item['ventaGravada'], 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    ---------------------------------------------------------------------------
</div>

<!-- Footer fijo -->
<div class="footer">
    Condicion Operación {{$datos["DTE"]['resumen']['condicionOperacion']}}
    <table>
        <tr>
            <td style="width: 100%">Total Operaciones:
        <tr>
            <td>{{ $datos["DTE"]['resumen']['totalLetras'] }} </td>
        </tr>
        <tr>
            <td>Total No Sujeto:</td>
            <td>${{ number_format($datos['DTE']['resumen']['totalNoSuj'], 2) }}</td>
        </tr>
        <tr>
            <td>Total Exento:</td>
            <td>${{ number_format($datos['DTE']['resumen']['totalExenta'], 2) }}</td>
        </tr>
        <tr>
            <td>Total Gravadas:</td>
            <td>${{ number_format($datos['DTE']['resumen']['totalGravada'], 2) }}</td>
        </tr>
        <tr>
            <td>Subtotal:</td>
            <td>${{ number_format($datos['DTE']['resumen']['subTotal'], 2) }}</td>
        </tr>
        @isset($datos['DTE']['resumen']['tributos'])
            @foreach($datos['DTE']['resumen']['tributos'] as $tributo)
                <tr>
                    <td>{{ $tributo['descripcion'] }}:</td>
                    <td>${{ number_format($tributo['valor'], 2) }}</td>
                </tr>
            @endforeach
        @endisset
        <tr>
            <td>
                <b>TOTAL A PAGAR:</b></td>
            <td> ${{number_format($datos['DTE']['resumen']['totalPagar'], 2)}}
            </td>
        </tr>
        </td>
        </tr>
    </table>


</div>
</body>
</html>
