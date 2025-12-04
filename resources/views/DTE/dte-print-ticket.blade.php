<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Tributario Electrónico</title>
    <style>
        body {
            font-family: Verdana, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 10px;
            text-align: center;
        }

        .header {
            width: 100%;
            margin-bottom: 10px;
        }

        .separator {
            border-top: 1px dashed #000;
            margin: 8px 0;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 10px;
        }

        .logo-container img {
            max-width: 120px;
            height: auto;
        }

        .qr-container {
            text-align: center;
            margin: 10px 0;
        }

        .qr-container img {
            max-width: 100px;
            height: auto;
        }

        .empresa-info {
            text-align: center;
            margin-bottom: 10px;
        }

        .empresa-info h4 {
            margin: 5px 0;
            font-size: 12px;
        }

        .empresa-info p {
            margin: 3px 0;
            line-height: 1.3;
        }

        .documento-info {
            text-align: left;
            margin: 10px 0;
        }

        .documento-info h4 {
            text-align: center;
            margin: 8px 0;
            font-size: 11px;
        }

        .documento-info h5 {
            text-align: center;
            margin: 5px 0;
            font-size: 10px;
        }

        .documento-info p {
            margin: 4px 0;
            word-wrap: break-word;
        }

        .cliente-info {
            text-align: left;
            margin: 10px 0;
        }

        .cliente-info p {
            margin: 3px 0;
            line-height: 1.4;
        }

        .tabla-productos {
            width: 100%;
            margin: 10px 0;
            text-align: left;
        }

        .tabla-productos .item-row {
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px dotted #ccc;
        }

        .tabla-productos .item-descripcion {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .tabla-productos .item-detalle-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
            margin-left: 10px;
        }

        .tabla-productos .item-detalle-table td {
            padding: 2px 5px;
            border: none;
            width: 33.33%;
        }

        .footer {
            text-align: left;
            margin-top: 10px;
            font-size: 9px;
        }

        .footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer td {
            padding: 2px 0;
        }

        .footer .total-final {
            font-weight: bold;
            font-size: 11px;
            margin-top: 5px;
            padding-top: 5px;
            border-top: 2px solid #000;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Logo Empresa -->
    <div class="logo-container">
        @if($datos['logo'])
            <img src="{{ $datos['logo'] }}" alt="Logo de la empresa">
        @endif
    </div>

    <!-- Información Empresa -->
    <div class="empresa-info">
        <h4>{{ $datos['empresa']['nombre'] }}</h4>
        <p>
            NIT: {{ $datos['empresa']['nit'] }}<br>
            NRC: {{ $datos['empresa']['nrc'] }}<br>
            {{ $datos['empresa']['descActividad'] }}<br>
            {{ $datos['empresa']['direccion']['complemento'] }}<br>
            Teléfono: {{ $datos['empresa']['telefono'] }}
        </p>
    </div>

    @php
        $ext = $datos['DTE']['extencion'] ?? $datos['DTE']['extension'] ?? null;
    @endphp
    <p style="text-align: left; margin: 8px 0;">
        <strong>Vendedor:</strong> {{ $ext['nombEntrega'] ?? 'Sin nombre' }}
    </p>

    <div class="separator"></div>

    <!-- Información del Documento -->
    <div class="documento-info">
        <h4>DOCUMENTO TRIBUTARIO ELECTRÓNICO</h4>
        <h5>{{ $datos['tipoDocumento'] }}</h5>

        <p>
            <strong>Código de generación:</strong><br>
            <span style="font-size: 8px; word-break: break-all;">
                {{ $datos['DTE']['respuestaHacienda']['codigoGeneracion'] ?? $datos['DTE']['identificacion']['codigoGeneracion'] }}
            </span>
        </p>

        <p>
            <strong>Número de control:</strong><br>
            {{ $datos['DTE']['identificacion']['numeroControl'] }}
        </p>

        <p>
            <strong>Sello de recepción:</strong><br>
            {{ $datos['DTE']['respuestaHacienda']['selloRecibido'] ?? 'CONTINGENCIA' }}
        </p>

        <p>
            <strong>Fecha y hora de emisión:</strong><br>
            {{ date('d/m/Y', strtotime($datos['DTE']['identificacion']['fecEmi'])) }}
            {{ $datos['DTE']['identificacion']['horEmi'] }}
        </p>
    </div>

    <div class="separator"></div>

    <!-- Información del Cliente -->
    <div class="cliente-info">
        <p><strong>CLIENTE</strong></p>
        <p>
            <strong>Razón Social:</strong> {{ $datos['DTE']['receptor']['nombre'] }}<br>
            <strong>Documento:</strong> {{ $datos['DTE']['receptor']['numDocumento'] ?? 'N/A' }}<br>
            @if(isset($datos['DTE']['receptor']['nrc']) && $datos['DTE']['receptor']['nrc'])
                <strong>NRC:</strong> {{ $datos['DTE']['receptor']['nrc'] }}<br>
            @endif
            @if(isset($datos['DTE']['receptor']['codActividad']) && $datos['DTE']['receptor']['codActividad'])
                <strong>Actividad:</strong> {{ $datos['DTE']['receptor']['codActividad'] }} - {{ $datos['DTE']['receptor']['descActividad'] ?? '' }}<br>
            @endif
            @if(isset($datos['DTE']['receptor']['direccion']['complemento']) && $datos['DTE']['receptor']['direccion']['complemento'])
                <strong>Dirección:</strong> {{ $datos['DTE']['receptor']['direccion']['complemento'] }}<br>
            @endif
            @if(isset($datos['DTE']['receptor']['telefono']) && $datos['DTE']['receptor']['telefono'])
                <strong>Teléfono:</strong> {{ $datos['DTE']['receptor']['telefono'] }}<br>
            @endif
            @if(isset($datos['DTE']['receptor']['correo']) && $datos['DTE']['receptor']['correo'])
                <strong>Correo:</strong> {{ $datos['DTE']['receptor']['correo'] }}
            @endif
        </p>
    </div>

    <div class="separator"></div>

    <!-- Productos -->
    <div class="tabla-productos">
        <p><strong>DETALLE DE PRODUCTOS/SERVICIOS</strong></p>

        @foreach ($datos['DTE']['cuerpo'] ?? $datos['DTE']['cuerpoDocumento'] as $item)
            <div class="item-row">
                <div class="item-descripcion">
                    {{ $item['cantidad'] }} x {{ $item['descripcion'] }}
                </div>
                <table class="item-detalle-table">
                    <tr>
                        <td style="text-align: left;">P.Unit: ${{ number_format($item['precioUni'], 2) }}</td>
                        <td style="text-align: center;">@if($item['montoDescu'] > 0)Desc: ${{ number_format($item['montoDescu'], 2) }}@endif</td>
                        <td style="text-align: right;"><strong>${{ number_format($item['ventaGravada'] ?? $item['compra'] ?? 0, 2) }}</strong></td>
                    </tr>
                </table>
            </div>
        @endforeach
    </div>

    <div class="separator"></div>

    <!-- Resumen/Footer -->
    <div class="footer">
        <p><strong>RESUMEN</strong></p>
        <table>
            <tr>
                <td><strong>Condición de Operación:</strong></td>
                <td class="text-right">
                    @php
                        $condicion = $datos['DTE']['resumen']['condicionOperacion'] ?? 1;
                        echo $condicion == 1 ? 'Contado' : ($condicion == 2 ? 'Crédito' : 'Otro');
                    @endphp
                </td>
            </tr>
            <tr>
                <td colspan="2" style="padding: 5px 0;">
                    <strong>Total en letras:</strong><br>
                    {{ $datos['DTE']['resumen']['totalLetras'] }}
                </td>
            </tr>
            <tr>
                <td>Total No Sujeto:</td>
                <td class="text-right">${{ number_format($datos['DTE']['resumen']['totalNoSuj'] ?? $datos['DTE']['resumen']['totalNoGravado'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Total Exento:</td>
                <td class="text-right">${{ number_format($datos['DTE']['resumen']['totalExenta'] ?? $datos['DTE']['resumen']['totalNoGravado'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Total Gravadas:</td>
                <td class="text-right">${{ number_format($datos['DTE']['resumen']['totalGravada'] ?? $datos['DTE']['resumen']['totalCompra'] ?? 0, 2) }}</td>
            </tr>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">${{ number_format($datos['DTE']['resumen']['subTotal'] ?? $datos['DTE']['resumen']['totalGravada'] ?? 0, 2) }}</td>
            </tr>
            @isset($datos['DTE']['resumen']['tributos'])
                @foreach($datos['DTE']['resumen']['tributos'] as $tributo)
                    <tr>
                        <td>{{ $tributo['descripcion'] }}:</td>
                        <td class="text-right">${{ number_format($tributo['valor'], 2) }}</td>
                    </tr>
                @endforeach
            @endisset
        </table>

        <div class="total-final text-center">
            TOTAL A PAGAR:
            @if(isset($datos['DTE']['resumen']['totalPagar']))
                ${{ number_format($datos['DTE']['resumen']['totalPagar'], 2) }}
            @elseif(isset($datos['DTE']['resumen']['montoTotalOperacion']))
                ${{ number_format($datos['DTE']['resumen']['montoTotalOperacion'], 2) }}
            @else
                $0.00
            @endif
        </div>
    </div>

    <div class="separator"></div>

    <!-- QR Code -->
    <div class="qr-container">
        <p style="margin-bottom: 5px;"><strong>Código QR</strong></p>
        @if($qr)
            <img src="{{ $qr }}" alt="Código QR">
        @endif
        <p style="font-size: 8px; margin-top: 5px;">Escanea para verificar el documento</p>
    </div>

    <div class="separator"></div>

    <p style="font-size: 8px; text-align: center; margin-top: 10px;">
        Gracias por su compra<br>
        Documento generado electrónicamente
    </p>
</body>
</html>
