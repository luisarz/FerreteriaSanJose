<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documento Tributario Electrónico</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10.5px;

        }

        .header {
            text-align: center;

        }


        .header img {
            width: 100px;
        }


        .tabla-productos th, .tabla-productos td {
            padding: 2px;
        }


        .resumen p {
            margin: 5px 0;
            text-align: right;
        }

        table {
            width: 100%;
            /*border-collapse: collapse;*/
        }

        tr {
            padding: 2px;
            /*border: 1px solid black;*/
        }

        td {
            padding: 4px;
            /*border: 1px solid black;*/
        }


        @page {
            margin: 5mm 0mm 0mm 15mm; /* Márgenes: arriba, derecha, abajo, izquierda */
        }


    </style>
</head>
<body>

<!-- Header Empresa -->
<div class="header">


    <table style="width: 100%; padding: 0; border-collapse: collapse;">
        <tr>
            <td style="width: 50%; text-align: left; vertical-align: middle; padding-right: 10px;">

                <img src="{{ asset($logoPath) }}" alt="Logo de la empresa" style="width: 150px; height: auto;">

            </td>

            <td style="width: 50%; text-align: left; vertical-align: middle; padding-left: 10px;">
                <h3>{{$empresa->name}}  {{$datos->whereHouse->name}}</h3></p>
                <p>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <h4>Orden de trabajo # <b>{{$datos->order_number}}</b></h4>
            </td>
        </tr>
    </table>
    ---------------------------------------------------------------------------

    <table width="100%" style="border: 0px solid black; border-collapse: collapse;padding: 10px !important;">
        <tbody>
        <tr>
            <td><b>FECHA</b></td>
            <td>{{date('d-m-Y H:s:i',strtotime($datos->created_at))}}</td>
        </tr>

        <tr>
            <td><b>Estado</b></td>
            <td>{{$datos->sale_status??''}}</td>
        </tr>
        <tr>
            <td><b>Cliente</b></td>
            <td>{{$datos->customer->name??''}} {{$datos->customer->last_name??''}}</td>
        </tr>
        <tr>
            <td><b>Teléfono</b></td>
            <td>{{$datos->customer->phone??''}}</td>
        </tr>
        <tr>
            <td><b>Dirección</b></td>
            <td>{{$datos->customer->address??''}}</td>
        </tr>
        <tr>
            <td><b>Vendedor</b></td>
            <td>{{$datos->seller->name??''}} {{$datos->seller->last_name??''}}</td>
        </tr>
        <tr>
            <td><b>Mecánico</b></td>
            <td>{{$datos->mechanic->name??'S/N'}} {{$datos->mechanic->lastname??''}}</td>
        </tr>
        </tbody>
    </table>

    ---------------------------------------------------------------------------
    <table width="100%" style="border: 0px solid black; border-collapse: collapse;">

        <tbody>
        @foreach ($datos->saleDetails as $item)
            @php($inventory = $item)
            <tr>
                <td>{{ $item->quantity }}</td>
                <td colspan="3  ">{{$item->inventory->product->name ?? '' }}</td>

            </tr>
            <tr>
                <td></td>
                <td colspan="3">
                    @if(!empty($item->inventory->product->sku))
                        <b> SKU {{ $item->inventory->product->sku }}</b>
                    @endif
                    @if(!empty($item->description))
                        <br/>
                        <b>DESCRIPCIÓN:</b> <br>
                        {{ $item->description ?? '' }}
                    @endif

                </td>
            </tr>
            <tr>
                <td></td>
                <td>${{ number_format($item->price??0, 2) }}</td>
                <td>Desc. ${{ number_format($item->discount, 2) }}</td>
                <td style="text-align: right">${{ number_format($item->total??0, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="3">TOTAL</td>
            <td style="text-align: right;"><b> ${{$datos->sale_total}}</b></td>
        </tr>
        </tfoot>
    </table>
    <p>
        ---------------------------------------------------------------------------
    </p>

    <table width="100%" style="border: 0px solid black; border-collapse: collapse;">
        <tbody>
        <tr>
            <td>
                <b>VALOR EN LETRAS:</b> <br> {{ $montoLetras ??''}}
            </td>
        </tr>
        </tbody>
    </table>
    <p>
        ---------------------------------------------------------------------------
    </p>

    <table width="100%" style="border: 0px solid black; border-collapse: collapse;">
        <tbody>
        <tr>
            <td style="background-color: #57595B; color: white; text-align: center; font-weight: bold; padding: 10px;">
                EXTENSIÓN - INFORMACIÓN ADICIONAL
            </td>
        </tr>
        <tr>
            <td style="padding: 5px; font-weight: bold;">Entregado por:</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid black;"></td>
        </tr>
        <tr>
            <td style="padding: 5px; font-weight: bold;">N° Documento:</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid black; "></td>
        </tr>
        <tr>
            <td style="padding: 5px; font-weight: bold;">Recibido por:</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid black;"></td>
        </tr>
        <tr>
            <td style="padding: 5px; font-weight: bold;">N° Documento:</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid black;"></td>
        </tr>
        <tr>
            <td style="padding: 5px; font-weight: bold;">Observaciones:</td>
        </tr>
        <tr>
            <td style="border-bottom: 1px solid black; height: 50px;"></td>
        </tr>
        </tbody>
    </table>

</div>


</body>
</html>
