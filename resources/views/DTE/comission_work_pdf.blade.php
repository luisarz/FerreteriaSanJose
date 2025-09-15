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
            min-height: 100vh;
        }

        .header {
            width: 100%;
            text-align: center;
            padding: 10px;
            box-shadow: 0 -1px 5px rgba(0, 0, 0, 0.1);
        }


        .footer {
            position: fixed;
            /*bottom: 0;*/
            /*background-color: #57595B;*/
            left: 0;
            width: 100%;
            border: 1px solid black; /* Borde sólido de 1px y color #f2f2f2 */
            text-align: right;
            font-size: 11px;
            padding: 1;
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


        .resumen p {
            margin: 5px 0;
            text-align: right;
        }

        .table {
            width: 100%;
            border: 1px solid black;
            /*border-collapse: collapse;*/
        }


        tfoot {
            border: 2px solid black;
        }

        tfoot tr {
            border-top: 2px solid black;
            border-bottom: 2px solid black;
        }


    </style>
</head>
<body>
<!-- Header Empresa -->
<div class="header">
    <table style="text-align: left; border:1px solid black; border-radius: 10px; width: 100%;">
        <tr>

            <td colspan="4" style="text-align: center;">

                <h2>{{$empresa->name}} | {{$sucursal->name}}</h2>
                <h4>REPORTE DE COMISIÓN DE VENTAS
                    Desde: {{date('d-m-Y',strtotime($startDate))}} - Hasta {{date('d-m-Y',strtotime($endDate))}}</h4>
                <h4>Vendedor:{{ strtoupper( $empleado->name.' '. $empleado->lastname) }}</h4>
            </td>


    </table>
    <!-- Tabla Productos -->
    @php
        $totalGeneral = ['amount' => 0, 'commission' => 0];
    @endphp

    <table style="border-collapse: collapse; width: 100%; margin-top: 20px; border: 1px solid black; font-size: 10px;">
        <thead>
        <tr style="background-color: #f0f0f0;">
            <th style="border: 1px solid black; padding: 6px;">Fecha</th>
            @foreach ($categories as $category)
                <th colspan="3" style="border: 1px solid black; padding: 6px;">{{ $category }}</th>
            @endforeach
            <th style="border: 1px solid black; padding: 6px;">Total Día</th>
            <th style="border: 1px solid black; padding: 6px;">Total Comisión</th>
        </tr>
        <tr style="background-color: #f0f0f0;">
            <th style="border: 1px solid black; padding: 6px;"></th>
            @foreach ($categories as $category)
                <th style="border: 1px solid black; padding: 6px;">Cant.</th>
                <th style="border: 1px solid black; padding: 6px;">Monto</th>
                <th style="border: 1px solid black; padding: 6px;">Comisión</th>
            @endforeach
            <th style="border: 1px solid black; padding: 6px;"></th>
            <th style="border: 1px solid black; padding: 6px;"></th>
        </tr>
        </thead>
        <tbody>
        @foreach ($pivotData as $date => $row)
            <tr>
                <td style="border: 1px solid black; padding: 6px;">{{ $date }}</td>
                @foreach ($categories as $category)
                    <td style="border: 1px solid black; padding: 6px;">{{ $date }}</td>
                    @foreach ($categories as $category)
                        <td style="border: 1px solid black; padding: 6px; max-width: 100px; word-wrap: break-word; white-space: normal;">
                            <b>{{ number_format($row[$category]['operations'] ?? 0, 0) }}</b>
                            @if (!empty($row[$category]['orders']))
                                <small>({{ $row[$category]['orders'] }})</small>
                            @endif
                        </td>
                        <td style="border: 1px solid black; padding: 6px;">{{ number_format($row[$category]['amount'] ?? 0, 2) }}</td>
                        <td style="border: 1px solid black; padding: 6px;">{{ number_format($row[$category]['commission'] ?? 0, 2) }}</td>
                    @endforeach
                    <td style="border: 1px solid black; padding: 6px;">
                        <strong>{{ number_format($row['Total Día'], 2) }}</strong></td>
                    <td style="border: 1px solid black; padding: 6px;">
                        <strong>{{ number_format($row['Total Comisión'], 2) }}</strong></td>

                    @php
                        $totalGeneral['amount'] += $row['Total Día'];
                        $totalGeneral['commission'] += $row['Total Comisión'];
                    @endphp
            </tr>
        @endforeach
        </tbody>

        <tfoot>
        <tr style="background-color: #e0e0e0; font-weight: bold;">
            <td style="border: 1px solid black; padding: 6px;">TOTAL GENERAL</td>
            @foreach ($categories as $category)
                <td style="border: 1px solid black; padding: 6px;"></td>
                <td style="border: 1px solid black; padding: 6px;"></td>
                <td style="border: 1px solid black; padding: 6px;"></td>
            @endforeach
            <td style="border: 1px solid black; padding: 6px;">{{ number_format($totalGeneral['amount'], 2) }}</td>
            <td style="border: 1px solid black; padding: 6px;">{{ number_format($totalGeneral['commission'], 2) }}</td>
        </tr>
        </tfoot>
    </table>


    <br>
    <br>
    <p style="text-align: left">
        F:Recibido: _____________________________
    </p>


</div>


<!-- Footer fijo -->
{{--<div class="footer">--}}
{{--    <table>--}}
{{--        <tr>--}}
{{--            <td style="width: 85%">--}}
{{--                <table style="width: 100%">--}}
{{--                    <tr>--}}
{{--                        <td colspan="2"><b>VALOR EN LETRAS:</b> {{ $montoLetras ??''}}--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                    <tr>--}}
{{--                        <td colspan="2" style="background-color: #57595B; color: white;  text-align: center;">--}}
{{--                            EXTENSIÓN-INFORMACIÓN ADICIONAL--}}
{{--                        </td>--}}
{{--                    </tr>--}}
{{--                    <tr>--}}
{{--                        <td>Entregado por:_____________________</td>--}}
{{--                        <td>Recibido por:_____________________</td>--}}
{{--                    </tr>--}}
{{--                    <tr>--}}
{{--                        <td>N° Documento:____________________</td>--}}
{{--                        <td>N° Documento:____________________</td>--}}
{{--                    </tr>--}}
{{--                    <tr>--}}
{{--                        <td>Condicion Operación:____________________</td>--}}
{{--                        --}}{{--                        <td>{{$datos["DTE"]['resumen']['condicionOperacion']??''}}</td>--}}
{{--                    </tr>--}}
{{--                    <tr>--}}
{{--                        <td colspan="2">Observaciones:</td>--}}
{{--                    </tr>--}}
{{--                </table>--}}
{{--            </td>--}}

{{--        </tr>--}}
{{--    </table>--}}
{{--</div>--}}
</body>
</html>
