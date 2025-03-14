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
            border-collapse: collapse;
        }

        .tabla-productos-anulado::before {
            content: "ANULADO";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px; /* Tamaño grande para el texto */
            font-weight: bold;
            color: rgba(0, 0, 0, 0.05); /* Negro con baja opacidad para efecto de marca de agua */
            z-index: 0;
            pointer-events: none; /* No afecta la interacción con la tabla */
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
                <h3>REPORTE DE COMISIÓN DE VENTAS</h3>
                <h3>Desde: {{date('d-m-Y',strtotime($startDate))}} - Hasta {{date('d-m-Y',strtotime($endDate))}}</h3>
                <h3>Vendedor:{{ strtoupper( $vendedor) }}</h3>


            </td>




    </table>
    <!-- Tabla Productos -->
    <table class="tabla-productos" width="100%" border="1" cellspacing="0" cellpadding="5">

        <thead style="border: 1px solid black;">
        <tr>
            <th style="width: 60px;" rowspan="2">Fecha</th>
            @foreach ($ventas[0]['categorias'] as $categoria => $porcentaje)
                @php
                    $category = $categoria .' '. $porcentaje ;
                @endphp

                <th colspan="2">{{ $category }}</th>

            @endforeach
            <th rowspan="2" >Total Ventas</th>
            <th rowspan="2">Total Comision</th>
        </tr>
        <tr>
            @foreach ($ventas[0]['categorias'] as $categoria => $porcentaje)
                <th>V</th>
                <th>C</th>

            @endforeach

        </tr>

        </thead>
        <tbody>
        @php
            $totalVentasGeneral = 0;
                  $totalComisionGeneral = 0;
        @endphp

        @foreach ($ventas[1]['ventasDiarias'] as $venta)
            <tr>

                @php
                    $totalVentasDiaria = 0;
                    $totalComisionDiaria = 0;

                @endphp

                <td>{{date('d-m-Y',strtotime( $venta['date'])) }}</td>
                @foreach ($venta['categories'] as $categoria => $detalle)
                    <td style="text-align: right;">
                        {{ $detalle['ventas'] > 0 ? '$ '.number_format($detalle['ventas'], 2) : '-' }}

                        @php
                            $totalVentasDiaria += number_format($detalle['ventas'],2, '.', '');
                            $totalComisionDiaria += number_format($detalle['comision_total'],2, '.', '');
                            $totalVentasGeneral += number_format($detalle['ventas'],2, '.', '');
                            $totalComisionGeneral += number_format($detalle['comision_total'],2, '.', '');
                        @endphp

                    </td>
                    <td style="text-align: right;">
                        {{ $detalle['comision_total'] > 0 ? '$ '.number_format($detalle['comision_total'], 2) : '-' }}

                    </td>
                @endforeach
                <th style="text-align: right;">$ {{number_format(($totalVentasDiaria),2)}}</th>
                <th style="text-align: right;">$ {{number_format($totalComisionDiaria,2)}}</th>
            </tr>

        @endforeach
        </tbody>
        <tfoot class="footer">
        @php
            $totalVentas = 0;
            $totalComision = 0;
        @endphp

        <tr style="border: black solid 2px;">
            <td>Totales</td>
            @foreach ($ventas[2]['total_by_category'] as $index => $venta)
                @php
                    $totalVentas += $venta;
                    $comision = $ventas[2]['comission_by_category'][$index]; // Obtener la comisión correspondiente
                    $totalComision += $comision;
                @endphp
                <td style="text-align: right">${{ number_format($venta, 2) }}</td>
                <td style="text-align: right">${{ number_format($comision, 2) }}</td>
            @endforeach
            <td style="text-align: right; font-size: 11px; background-color: #66FFB2" >$ {{number_format($totalVentasGeneral,2)}}</td>
            <td style="text-align: right; font-size: 11px; background-color: #66FFB2">$ {{number_format($totalComisionGeneral,2)}}</td>
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
