<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Tributario Electrónico</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            /*min-height: 100vh;*/
        }

        .header {
            width: 100%;
            text-align: center;
            padding: 10px;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
        }


        .footer {
            /*position: fixed;*/
            /*bottom: 0;*/
            /*background-color: #57595B;*/
            left: 0;
            width: 100%;
            border: 1px solid black; /* Borde sólido de 1px y color #f2f2f2 */
            border-radius: 10px; /* Radio redondeado de 10px */
            text-align: right;
            font-size: 12px;
            /*padding: 3;*/
        }

        .content {
            flex: 1;
            padding-bottom: 100px; /* Espacio para el footer */
        }

        .header img {
            width: 100px;
        }

        .empresa-info, .documento-info, .tabla-productos, .resumen {
            margin: 10px 0;
        }

        .tabla-productos th, .tabla-productos td {
            padding: 5px;
        }

        .tabla-productos th {
            background-color: #f2f2f2;
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
    <table style="width: 100%">
        <tr>
            <td style="width: 40%; ">
                <table style="text-align: left; border: black solid 1px; border-radius: 10px;">
                    <tr>
                        <td style="width: 5%">
                            <img src="{{ asset($datos['logo'] ?? '') }}" alt="Logo Empresa">
                        </td>
                        <td style="width: 95%">
                            <h3>{{ $datos['empresa']['nombre'] }}</h3>
                            <p style="font-size: 12px; line-height: 1;">
                                NIT: {{ $datos['empresa']['nit'] }}<br>
                                NRC: {{ $datos['empresa']['nrc'] }}
                            </p>
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
            </td>
            <td style="width: 55%; text-align: left; border: black solid 1px; border-radius: 10px; font-size: 11px;">
                <div style="text-align: center;">
                    <h3>DOCUMENTO TRIBUTARIO ELECTRÓNICO</h3>
                    <h3>{{ $datos['tipoDocumento'] }}</h3>
                </div>
                <table>
                    <tr>
                        <td>Código generación:</td>
                        <td>
                            {{ $datos['DTE']['respuestaHacienda']['codigoGeneracion'] ?? $datos['DTE']['identificacion']['codigoGeneracion'] }}

                        </td>
                    </tr>
                    <tr>
                        <td>Sello de recepción:</td>
                        <td>{{ $datos['DTE']['respuestaHacienda']['selloRecibido']??'Contingencia' }}</td>
                    </tr>
                    <tr>
                        <td>Número de control:</td>
                        <td>{{ $datos['DTE']['identificacion']['numeroControl'] }}</td>
                    </tr>
                    <tr>
                        <td>Fecha emisión:</td>
                        <td>{{ date('d-m-Y', strtotime($datos['DTE']['identificacion']['fecEmi'])) }}</td>
                    </tr>
                    <tr>
                        <td>Hora emisión:</td>
                        <td>{{ $datos['DTE']['identificacion']['horEmi'] }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<!-- Contenido principal -->
<div class="content">
    <!-- Info Cliente -->
    <div class="cliente-info">
        <table>
            <tr>
                <td>
                    <p>Razón Social: {{ $datos['DTE']['receptor']['nombre']??'' }}<br>
                        Documento: {{ $datos['DTE']['receptor']['numDocumento'] ?? '' }}<br>
                        NRC: {{ $datos['DTE']['receptor']['nrc']??'' }}<br>
                        Actividad: {{ $datos['DTE']['receptor']['codActividad']??'' }}
                        - {{ $datos['DTE']['receptor']['descActividad']??'' }}<br>
                        Dirección: {{ $datos['DTE']['receptor']['direccion']['complemento']??'' }}<br>
                        Teléfono: {{ $datos['DTE']['receptor']['telefono']??'' }} |
                        Correo: {{ $datos['DTE']['receptor']['correo']??'' }}
                    </p>
                </td>
                <td style="align-items: end;">
                    <img src="{{ asset($qr) }}" alt="QR Código" width="100px">
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabla Productos -->
    <table class="tabla-productos" width="100%" border="1" cellspacing="0" cellpadding="5">
        <thead>
        <tr>
            <th>No</th>
            <th>Cant</th>
            <th>Unidad</th>
            <th>Descripción</th>
            <th>Precio Unitario</th>
            <th>Desc Item</th>
            <th>Ventas No Sujetas</th>
            <th>Ventas Exentas</th>
            <th>Ventas Gravadas</th>
        </tr>
        </thead>
        <tbody>

        @foreach ($datos['DTE']['cuerpo']??$datos['DTE']['cuerpoDocumento'] as $item)
            <tr>
                <td>{{ $item['numItem'] }}</td>
                <td>{{ $item['cantidad'] }}</td>
                <td>{{ $item['uniMedida'] }}</td>
                <td>{{ $item['descripcion'] }}</td>
                <td>${{ number_format($item['precioUni'], 2) }}</td>
                <td>${{ number_format($item['montoDescu'], 2) }}</td>
                <td>${{ number_format($item['ventaNoSuj']??$item['noGravado']??0, 2) }}</td>
                <td>${{ number_format($item['ventaExenta']??$item['noGravado']??0, 2) }}</td>
                <td>${{ number_format($item['ventaGravada']??$item['compra']??0, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<!-- Footer fijo -->
<div class="footer">

    <table>
        <tr>
            <td style="width: 50%">
                <table style="width: 100%">
                    <tr>
                        <td colspan="2"><b>VALOR EN LETRAS:</b> {{ $datos["DTE"]['resumen']['totalLetras'] }} DOLARES
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="background-color: #57595B; color: white;  text-align: center;">
                            EXTENSIÓN-INFORMACIÓN ADICIONAL
                        </td>
                    </tr>
                    <tr>
                        @php
                            $ext = $datos['DTE']['extencion'] ?? $datos['DTE']['extension'] ?? null;
                        @endphp
                        <td>Entregado por:   {{  $ext['nombEntrega']??'S/N' }}</td>
                        <td>Recibido por:</td>
                    </tr>
                    <tr>
                        <td>N° Documento:</td>
                        <td>N° Documento:</td>
                    </tr>
                    <tr>
                        <td>Condicion Operación</td>
{{--                        <td>{{$datos["DTE"]['resumen']['condicionOperacion']}}</td>--}}
                        <td>
                            @if(isset($datos['DTE']['resumen']['totalPagar']))
                                ${{ number_format($datos['DTE']['resumen']['totalPagar'], 2) }}
                            @elseif(isset($datos['DTE']['resumen']['montoTotalOperacion']))
                                ${{ number_format($datos['DTE']['resumen']['montoTotalOperacion'], 2) }}
                            @elseif(isset($datos['DTE']['resumen']['totalLetras']))
                                {{ $datos['DTE']['resumen']['totalLetras'] }}
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">Observaciones:</td>
                    </tr>
                </table>
            </td>
            <td style="width: 20%">Total Operaciones:
                <table style="width: 100%">
                    <tr>
                        <td>Total No Sujeto:</td>
                        <td>${{ number_format($datos['DTE']['resumen']['totalNoSuj']??$datos['DTE']['resumen']['totalNoGravado']??0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total Exento:</td>
                        <td>${{ number_format($datos['DTE']['resumen']['totalExenta']??$datos['DTE']['resumen']['totalNoGravado']??0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total Gravadas:</td>
                        <td>${{ number_format($datos['DTE']['resumen']['totalGravada']??$datos['DTE']['resumen']['totalCompra'], 2) }}</td>
                    </tr>
                    <tr>
                        <td>Subtotal:</td>
                        <td>${{ number_format($datos['DTE']['resumen']['subTotal']??$datos['DTE']['resumen']['totalGravada'], 2) }}</td>
                    </tr>
                    @isset($datos['DTE']['resumen']['tributos'])
                        @foreach($datos['DTE']['resumen']['tributos'] as $tributo)
                            <tr>
                                <td>{{ $tributo['descripcion'] }}:</td>
                                <td>${{ number_format($tributo['valor'], 2) }}</td>
                            </tr>
                        @endforeach
                    @endisset
                    <tr style="background-color: #57595B; color: white;">
                        <td>
                            <b>TOTAL A PAGAR:</b></td>
                        <td> ${{number_format($datos['DTE']['resumen']['totalPagar']??$datos['DTE']['resumen']['montoTotalOperacion'], 2)}}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>


</div>
</body>
</html>
