<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Tributario Electrónico</title>

    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.5px;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            line-height: 1.3;
            color: #222;
        }

        .header {
            width: 100%;
            text-align: center;
            margin-bottom: 8px;
        }

        .header img {
            width: 200px;
        }

        .header strong {
            font-weight: bold;
            color: #57595B;
            font-size: 9.5px;
        }

        .footer {
            left: 0;
            width: 100%;
            border: 2px solid #e85d04;
            border-radius: 10px;
            text-align: right;
            font-size: 9px;
            line-height: 1.3;
        }

        .footer strong {
            font-weight: bold;
            color: #1e6bb8;
        }

        .content {
            flex: 1;
            padding-bottom: 100px;
        }

        .content strong,
        .content b {
            font-weight: bold;
            color: #57595B;
            font-size: 9.5px;
        }

        .empresa-info, .documento-info, .tabla-productos, .resumen {
            margin: 10px 0;
        }

        .tabla-productos {
            border-collapse: collapse;
            width: 100%;
        }

        .tabla-productos th {
            background-color: #1e6bb8 !important;
            color: #ffffff !important;
            font-weight: bold;
            padding: 8px 5px;
            text-align: center;
            font-size: 9px;
            border: 1px solid #1e6bb8;
            line-height: 1.2;
        }

        .tabla-productos td {
            padding: 6px 5px;
            border: 1px solid #d0d0d0;
            font-size: 9px;
            line-height: 1.3;
        }

        .tabla-productos tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .tabla-productos tbody tr:nth-child(odd) {
            background-color: #ffffff;
        }

        .tabla-productos td:nth-child(1),
        .tabla-productos td:nth-child(2) {
            text-align: center;
            font-weight: normal;
        }

        .tabla-productos td:nth-child(6),
        .tabla-productos td:nth-child(7),
        .tabla-productos td:nth-child(8),
        .tabla-productos td:nth-child(9),
        .tabla-productos td:nth-child(10) {
            text-align: right;
            font-weight: normal;
        }

        .cliente-info {
            line-height: 1.4;
        }

        .cliente-info tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .cliente-info td {
            padding: 5px 6px;
            font-size: 9.5px;
        }

        .cliente-info b {
            color: #57595B;
            font-weight: bold;
        }

        .content > div[style*="border-bottom"] {
            font-size: 11px;
            font-weight: bold;
            color: #57595B;
            line-height: 1.3;
        }

        .resumen p {
            margin: 5px 0;
            text-align: right;
            line-height: 1.3;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        body > div:last-child p {
            font-size: 7.5px;
            line-height: 1.4;
            color: #555;
        }
    </style>
</head>
<body>
<!-- Header Empresa -->
<div class="header" style="border: 0px solid #ccc; border-radius: 10px; padding: 0px; font-family: Arial, sans-serif;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            {{-- IZQUIERDA: LOGO Y EMPRESA --}}
            <td style="width: 40%; vertical-align: top; border: none; border-left: 2px solid #e85d04; padding-left: 8px; padding-right: 10px;">
                <table style="width: 100%; font-size: 10px; font-family: Arial, sans-serif; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 4px 0; width: 90px; vertical-align: middle;">
                            @if($datos['logo'] && file_exists($datos['logo']))
                                <img src="{{ $datos['logo'] }}" alt="Logo Empresa" style="max-height: 75px; max-width: 85px;">
                            @endif
                        </td>
                        <td style="vertical-align: middle; padding: 4px 0 4px 10px;">
                            <div style="font-weight: bold; font-size: 12px; padding: 0 0 3px 0; color: #1e6bb8; line-height: 1.2;">
                                {{ $datos['empresa']['nombre'] ?? 'NOMBRE DE EMPRESA' }}
                            </div>
                            <div style="padding: 0; font-size: 10px; color: #333; line-height: 1.3;">
                                {{ $datos['empresa']['descActividad'] ?? '' }}
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 6px 0 0 0;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr style="background-color: #f5f5f5;">
                                    <td style="text-align: left; padding: 5px 6px; font-weight: bold; color: #57595B; font-size: 10px; width: 35%; line-height: 1.3; vertical-align: top;">
                                        NIT:
                                    </td>
                                    <td style="text-align: left; padding: 5px 6px; color: #333; font-size: 9.5px; text-transform: uppercase; line-height: 1.3;">
                                        @php
                                            $nit = $datos['empresa']['nit'] ?? '';
                                        @endphp
                                        {{ strlen($nit) === 14 ? substr($nit, 0, 4) . '-' . substr($nit, 4, 6) . '-' . substr($nit, 10, 3) . '-' . substr($nit, 13, 1) : $nit }}
                                    </td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="text-align: left; padding: 5px 6px; font-weight: bold; color: #57595B; font-size: 10px; line-height: 1.3; vertical-align: top;">
                                        NRC:
                                    </td>
                                    <td style="text-align: left; padding: 5px 6px; color: #333; font-size: 9.5px; text-transform: uppercase; line-height: 1.3;">{{ $datos['empresa']['nrc'] ?? '' }}</td>
                                </tr>
                                <tr style="background-color: #f5f5f5;">
                                    <td style="text-align: left; padding: 5px 6px; font-weight: bold; color: #57595B; font-size: 10px; line-height: 1.3; vertical-align: top;">
                                        DIRECCIÓN:
                                    </td>
                                    <td style="text-align: left; padding: 5px 6px; color: #333; font-size: 9.5px; text-transform: uppercase; line-height: 1.3;">{{ $datos['empresa']['direccion']['complemento'] ?? '' }}</td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="text-align: left; padding: 5px 6px; font-weight: bold; color: #57595B; font-size: 10px; line-height: 1.3; vertical-align: top;">
                                        TELÉFONO:
                                    </td>
                                    <td style="text-align: left; padding: 5px 6px; color: #333; font-size: 9.5px; text-transform: uppercase; line-height: 1.3;">
                                        @php
                                            $telefono = $datos['empresa']['telefono'] ?? '';
                                            $telefonoLimpio = preg_replace('/[^0-9]/', '', $telefono);

                                            if (strlen($telefonoLimpio) == 11 && substr($telefonoLimpio, 0, 3) == '503') {
                                                $telefono = '(' . substr($telefonoLimpio, 0, 3) . ') ' . substr($telefonoLimpio, 3, 4) . '-' . substr($telefonoLimpio, 7, 4);
                                            }
                                            elseif (strlen($telefonoLimpio) == 8) {
                                                $telefono = '(' . substr($telefonoLimpio, 0, 4) . ') ' . substr($telefonoLimpio, 4, 4);
                                            }
                                        @endphp
                                        {{ $telefono }}
                                    </td>
                                </tr>
                                <tr style="background-color: #f5f5f5;">
                                    <td style="text-align: left; padding: 5px 6px; font-weight: bold; color: #57595B; font-size: 10px; line-height: 1.3; vertical-align: top;">
                                        CORREO:
                                    </td>
                                    <td style="text-align: left; padding: 5px 6px; color: #333; font-size: 9.5px; text-transform: uppercase; line-height: 1.3;">{{ $datos['empresa']['correo'] ?? '' }}</td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="text-align: left; padding: 5px 6px; font-weight: bold; color: #57595B; font-size: 10px; line-height: 1.3; vertical-align: top;">
                                        SITIO WEB:
                                    </td>
                                    <td style="text-align: left; padding: 5px 6px; color: #333; font-size: 9.5px; text-transform: uppercase; line-height: 1.3;">{{ $datos['empresa']['web'] ?? '' }}</td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>

            {{-- DERECHA: DTE INFO --}}
            <td style="width: 60%; vertical-align: top; border: none;">
                <table style="width: 100%; border-collapse: collapse; font-size: 10px; border: none;">
                    <thead>
                    <th style="width: 110px !important; border: none;"></th>
                    <th style="border: none;"></th>
                    </thead>
                    <tr style="background-color: #1e6bb8; color: white;">
                        <td colspan="2" style="text-align: center; font-size: 10px; font-weight: bold; padding: 4px; line-height: 1.1; border: none;">
                            DOCUMENTO TRIBUTARIO ELECTRÓNICO
                        </td>
                    </tr>
                    <tr style="background-color: #1e6bb8; color: white;">
                        <td colspan="2" style="text-align: center; font-size: 13px; font-weight: bold; padding: 5px; line-height: 1.1; border: none;">
                            {{ $datos['tipoDocumento'] }}
                        </td>
                    </tr>
                    <tr style="background-color: #f5f5f5;">
                        <td style="font-weight: bold; color: #57595B; padding: 5px 6px; font-size: 10px; line-height: 1.3; vertical-align: top; border: none;">
                            Código generación:
                        </td>
                        <td style="color: #333; padding: 5px 6px; font-size: 9.5px; line-height: 1.3; word-break: break-all; border: none;">
                            {{ $datos['DTE']['respuestaHacienda']['codigoGeneracion'] ?? $datos['DTE']['identificacion']['codigoGeneracion'] }}
                        </td>
                    </tr>
                    <tr style="background-color: #ffffff;">
                        <td style="font-weight: bold; color: #57595B; padding: 5px 6px; font-size: 10px; line-height: 1.3; vertical-align: top; border: none;">
                            Sello de recepción:
                        </td>
                        <td style="color: #333; padding: 5px 6px; font-size: 9.5px; line-height: 1.3; word-break: break-all; border: none;">
                            {{ $datos['DTE']['respuestaHacienda']['selloRecibido'] ?? 'Contingencia' }}
                        </td>
                    </tr>
                    <tr style="background-color: #f5f5f5;">
                        <td style="font-weight: bold; color: #57595B; padding: 5px 6px; font-size: 10px; line-height: 1.3; border: none;">
                            Número de control:
                        </td>
                        <td style="color: #333; padding: 5px 6px; font-size: 10px; line-height: 1.3; border: none;">
                            {{ $datos['DTE']['identificacion']['numeroControl'] }}
                        </td>
                    </tr>
                    <tr style="background-color: #ffffff;">
                        <td style="font-weight: bold; color: #57595B; padding: 5px 6px; font-size: 10px; line-height: 1.3; border: none;">
                            Fecha emisión:
                        </td>
                        <td style="color: #333; padding: 5px 6px; font-size: 10px; line-height: 1.3; border: none;">
                            {{ date('d-m-Y', strtotime($datos['DTE']['identificacion']['fecEmi'])) }}
                        </td>
                    </tr>
                    <tr style="background-color: #f5f5f5;">
                        <td style="font-weight: bold; color: #57595B; padding: 5px 6px; font-size: 10px; line-height: 1.3; border: none;">
                            Hora emisión:
                        </td>
                        <td style="color: #333; padding: 5px 6px; font-size: 10px; line-height: 1.3; border: none;">
                            {{ $datos['DTE']['identificacion']['horEmi'] }}
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align: center; padding: 8px 4px; border: none;">
                            @if($qr && file_exists($qr))
                                <img src="{{ $qr }}" alt="QR Código"
                                     style="width: 100px; height: 100px; border: 2px solid #999; padding: 5px;">
                            @endif
                        </td>
                        <td style="padding: 0 4px; border: none;">
                            <table style="width: 100%; border-collapse: collapse; font-size: 9px; border: none;">
                                @php
                                    $variables = [];

                                    if (!empty($datos['DTE']['apendice']) && is_array($datos['DTE']['apendice'])) {
                                        foreach ($datos['DTE']['apendice'] as $item) {
                                            $variables[$item['campo']] = $item['valor'];
                                        }
                                    }

                                    $codCliente   = $variables['COD_CLIENTE'] ?? '-';
                                    $vendedor     = $variables['VENDEDOR'] ?? '-';
                                    $almacen      = $variables['ALMACEN'] ?? '-';
                                    $orderNumber  = $variables['ORDER_NUMBER'] ?? '-';
                                    $creditDays   = $variables['CREDIT_DAYS'] ?? '-';
                                @endphp
                                <thead>
                                <tr>
                                    <td colspan="2" style="text-align: center; font-weight: bold; padding: 5px; background-color: #1e6bb8; color: white; font-size: 9px; line-height: 1.2;">
                                        DETALLES ADICIONALES
                                    </td>
                                </tr>
                                </thead>
                                <tbody>
                                <tr style="background-color: #f5f5f5;">
                                    <td style="text-align: left; padding: 4px 6px; font-weight: bold; color: #57595B; font-size: 8px; width: 45%;">
                                        CÓDIGO CLIENTE:
                                    </td>
                                    <td style="text-align: left; padding: 4px 6px; color: #333; font-size: 8.5px;">{{$codCliente}}</td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="text-align: left; padding: 4px 6px; font-weight: bold; color: #57595B; font-size: 8px;">
                                        VENDEDOR:
                                    </td>
                                    <td style="text-align: left; padding: 4px 6px; color: #333; font-size: 8.5px;">{{$vendedor}}</td>
                                </tr>
                                <tr style="background-color: #f5f5f5;">
                                    <td style="text-align: left; padding: 4px 6px; font-weight: bold; color: #57595B; font-size: 8px;">
                                        ALMACÉN:
                                    </td>
                                    <td style="text-align: left; padding: 4px 6px; color: #333; font-size: 8.5px;">{{substr($almacen,0,28)}}</td>
                                </tr>
                                <tr style="background-color: #ffffff;">
                                    <td style="text-align: left; padding: 4px 6px; font-weight: bold; color: #57595B; font-size: 8px;">
                                        ORDEN DE COMPRA:
                                    </td>
                                    <td style="text-align: left; padding: 4px 6px; color: #333; font-size: 8.5px;">{{$orderNumber}}</td>
                                </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<!-- Contenido principal -->
<div class="content">
    <div style="border-bottom: 1px solid black; font-weight: bold; font-size: 14px; font-family: Arial, sans-serif; color: #333; padding-bottom: 4px;">
        Información del receptor
    </div>

    <!-- Info Cliente -->
    <div class="cliente-info">
        <table style="width: 100%; border-collapse: collapse; font-size: 9px; font-family: Arial, sans-serif; ">
            <tr>
                <td style="padding: 4px 2px 4px 0; width: "><b>NOMBRE:</b></td>
                <td style="padding: 4px 6px;">{{ $datos['DTE']['receptor']['nombre']??'' }}</td>
                <td style="padding: 4px 2px 4px 0;"><b>NIT:</b></td>
                <td style="padding: 4px 6px;">{{ $datos['DTE']['receptor']['nit'] ?? '' }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 2px 4px 0;"><b>ACTIVIDAD:</b></td>
                <td style="padding: 4px 6px;">{{ $datos['DTE']['receptor']['codActividad']??'' }}
                    @if(!empty($datos['DTE']['receptor']['descActividad']))
                        - {{ $datos['DTE']['receptor']['descActividad'] }}
                    @endif
                </td>
                <td style="padding: 4px 2px 4px 0;"><b>NRC:</b></td>
                <td style="padding: 4px 6px;">{{ $datos['DTE']['receptor']['nrc']??'' }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 2px 4px 0;"><b>DIRECCIÓN:</b></td>
                <td style="padding: 4px 6px;">{{ $datos['DTE']['receptor']['direccion']['complemento']??'' }}</td>
                <td style="padding: 4px 2px 4px 0;"><b>TELÉFONO:</b></td>
                <td style="padding: 4px 6px;">{{ $datos['DTE']['receptor']['telefono']??'' }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 2px 4px 0; vertical-align: top;"><b>REFERENCIAS:</b></td>
                <td style="padding: 4px 6px;"></td>
                <td style="padding: 4px 2px 4px 0; vertical-align: top;"><b>CORREO:</b></td>
                <td style="padding: 4px 6px; word-break: break-all; ">
                    {{ $datos['DTE']['receptor']['correo']??'' }}
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
            <th>Unidad de medida</th>
            <th>Código</th>
            <th>Descripción</th>
            <th>Precio Unitario</th>
            <th>Desc Item</th>
            <th>Ventas No Sujetas</th>
            <th>Ventas Exentas</th>
            <th>Ventas Gravadas</th>
        </tr>
        </thead>
        <tbody>
        @php
            $unitMeasurements = \App\Models\UnitMeasurement::pluck('description', 'code')->toArray();
        @endphp

        @foreach ($datos['DTE']['cuerpo']??$datos['DTE']['cuerpoDocumento'] as $item)
            @php
                $unidad = $unitMeasurements[$item['uniMedida']] ?? $item['uniMedida'];
            @endphp

            <tr>
                <td>{{ $item['numItem'] }}</td>
                <td>{{ $item['cantidad'] }}</td>
                <td>{{ $unidad }}</td>
                <td>{{ $item['codigo'] ?? '' }}</td>
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
    <table style="width: 100%; border-collapse: collapse; font-size: 10px; font-family: Arial, sans-serif;">
        <tr>
            <td style="width: 60%">
                <table style="width: 100%">
                    <tr>
                        <td colspan="2"><b>VALOR EN LETRAS:</b> {{ $datos["DTE"]['resumen']['totalLetras'] }} DOLARES
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="background-color: #1e6bb8; color: white; text-align: center;">
                            EXTENSIÓN-INFORMACIÓN ADICIONAL
                        </td>
                    </tr>
                    <tr>
                        @php
                            $ext = $datos['DTE']['extencion'] ?? $datos['DTE']['extension'] ?? null;
                        @endphp
                        <td>Entregado por: {{ $ext['nombEntrega']??'S/N' }}</td>
                        <td>Recibido por:</td>
                    </tr>
                    <tr>
                        <td>N° Documento:</td>
                        <td>N° Documento:</td>
                    </tr>
                    <tr>
                        <td>Condición Operación</td>
                        <td>
                            @if ($datos["DTE"]['resumen']['condicionOperacion'] == 1)
                                Contado
                            @elseif ($datos["DTE"]['resumen']['condicionOperacion'] == 2)
                                Crédito a {{ $creditDays }} días
                            @else
                                Otro
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">Observaciones:</td>
                    </tr>
                </table>
            </td>
            <td style="width: 40%">Total Operaciones:
                <table style="width: 100%">
                    <tr>
                        <td>Total No Sujeto:</td>
                        <td>
                            ${{ number_format($datos['DTE']['resumen']['totalNoSuj']??$datos['DTE']['resumen']['totalNoGravado']??0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total Exento:</td>
                        <td>
                            ${{ number_format($datos['DTE']['resumen']['totalExenta']??$datos['DTE']['resumen']['totalNoGravado']??0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total Gravadas:</td>
                        <td>
                            ${{ number_format($datos['DTE']['resumen']['totalGravada']??$datos['DTE']['resumen']['totalCompra']??0, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Subtotal:</td>
                        <td>
                            ${{ number_format($datos['DTE']['resumen']['subTotal']??$datos['DTE']['resumen']['totalGravada']??0, 2) }}</td>
                    </tr>
                    @isset($datos['DTE']['resumen']['tributos'])
                        @foreach($datos['DTE']['resumen']['tributos'] as $tributo)
                            <tr>
                                <td>{{ $tributo['descripcion'] }}:</td>
                                <td>${{ number_format($tributo['valor'], 2) }}</td>
                            </tr>
                        @endforeach
                    @endisset
                    <tr style="background-color: #e85d04; color: white;">
                        <td>
                            <b>TOTAL A PAGAR:</b></td>
                        <td>
                            ${{ number_format($datos['DTE']['resumen']['totalPagar']??$datos['DTE']['resumen']['montoTotalOperacion']??0, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<div>
    <p style="text-align:center; font-size: 8px; font-family: Arial, sans-serif; margin-top: 10px; font-weight: bold;">
        EL INCUMPLIMIENTO DEL PAGO DE ESTE DOCUMENTO AL CRÉDITO EN EL PLAZO ESTIPULADO. GENERARÁ UN CARGO POR MORA DEL 2% MENSUAL SOBRE SALDO VENCIDO. TODO CHEQUE RECHAZADO, GENERARÁ UN CARGO ADMINISTRATIVO DE $10.00, NO SE ACEPTAN CAMBIOS NI DEVOLUCIONES DESPUÉS DE 7 DÍAS CALENDARIO, EN MATERIALES ELÉCTRICOS NO ADMITIMOS DEVOLUCIONES
    </p>
</div>
</body>
</html>
