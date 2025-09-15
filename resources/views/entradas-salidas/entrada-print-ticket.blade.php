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
                <h3>{{$empresa->name}}  {{$datos->branch->name}}</h3></p>
                <p>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <h4>{{$datos->tipo}}  # <b>{{$datos->id}}</b></h4>
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
            <td>{{$datos->status??''}}</td>
        </tr>
        <tr>
            <td><b>Cliente</b></td>
            <td>{{$datos->entidad??''}} </td>
        </tr>


        <tr>
            <td><b>Vendedor</b></td>
            <td>{{$datos->employee->name??''}} {{$datos->employee->last_name??''}}</td>
        </tr>

        </tbody>
    </table>

    ---------------------------------------------------------------------------
    <table width="100%" style="border: 0px solid black; border-collapse: collapse;">

        <tbody>
        @foreach ($datos->adjustItems as $item)
            @php($inventory = $item)
            <tr>
                <td>{{ $item->cantidad }}</td>
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
                <td>${{ number_format($item->precio_unitario??0, 2) }}</td>
                <td>Desc. ${{ number_format($item->discount, 2) }}</td>
                <td style="text-align: right">${{ number_format($item->total??0, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot>
        <tr>
            <td colspan="3">TOTAL</td>
            <td style="text-align: right;"><b> ${{$datos->monto}}</b></td>
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
            <td style="border-bottom: 1px solid black; height: 50px;">
                {{$datos->descripcion}}
            </td>
        </tr>
        </tbody>
    </table>

</div>


</body>
</html>
