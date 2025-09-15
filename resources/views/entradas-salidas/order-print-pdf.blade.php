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
            border-radius: 10px; /* Radio redondeado de 10px */
            text-align: right;
            font-size: 12px;
            padding: 3;
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
    </style>
</head>
<body>
<!-- Header Empresa -->
<div class="header">
    <table     style="text-align: left; border:1px solid black; border-radius: 10px; width: 100%;">
        <tr>

            <td colspan="4" style="text-align: center;">

                <h2>{{$empresa->name}} | {{$datos->whereHouse->name}}</h2>
                <h3>REPORTE DE ENVIO DE PRODUCTOS</h3>

            </td>
        <tr>
            <td>N° de Documento: <b>{{$datos->order_number}}</b></td>
            <td>TIPO: Salida de prodúctos / Orden de trabajo </td>
            <td>FECHA:{{date('d-m-Y H:s:i',strtotime($datos->created_at))}} </td>
            <td>Vendedor:{{$datos->seller->name??''}} {{$datos->seller->last_name??''}} </td>
        </tr>
        <tr>
            <td>Estado: <b>{{$datos->sale_status??''}}</b></td>
            <td colspan="1">Destino/Cliente: {{$datos->customer->name??'' ." ". $datos->customer->last_name??''}} // {{$datos->customer->address??''}}</td>
            <td colspan="2">Mecanico: {{$datos->mechanic->name??'S/N'}} {{$datos->mechanic->lastname??''}}</td>
        </tr>

    </table>
    <!-- Tabla Productos -->
    <table class="tabla-productos{{ $datos->sale_status == 'Anulado' ? '-anulado' : '' }}" width="100%" border="1" cellspacing="0" cellpadding="5">

    <thead style="border: 1px solid black;">
        <tr>
            <th>No</th>
            <th>Cant</th>
            <th>Unidad</th>
            <th>Descripción</th>
            <th>Precio Unitario</th>
            <th>Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($datos->saleDetails as $item)
           @php($inventory = $item)

            <tr>
                <td>{{ $loop->iteration}}</td>
                <td>{{ $item->quantity }}</td>
                <td>Unidad</td>
                <td>
                    {{ $item->inventory->product->name ?? '' }}
                    @if(!empty($item->inventory->product->sku))
                        <b> SKU {{ $item->inventory->product->sku }}</b>
                    @endif
                    @if(!empty($item->description))
                        <br> <b>DESCRIPCIÓN:</b> <br>
                        {{ $item->description ?? '' }}
                    @endif

                </td>

                <td>${{ number_format($item->price??0, 2) }}</td>
                <td>${{ number_format($item->total??0, 2) }}</td>
            </tr>
    @endforeach
    {{$datos}}
</div>


<!-- Footer fijo -->
<div class="footer">

        <table>
            <tr>
                <td style="width: 85%">
                    <table style="width: 100%">
                        <tr>
                            <td colspan="2"><b>VALOR EN LETRAS:</b> {{ $montoLetras ??''}}
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="background-color: #57595B; color: white;  text-align: center;">
                                EXTENSIÓN-INFORMACIÓN ADICIONAL
                            </td>
                        </tr>
                        <tr>
                            <td>Entregado por:_____________________</td>
                            <td>Recibido por:_____________________</td>
                        </tr>
                        <tr>
                            <td>N° Documento:____________________</td>
                            <td>N° Documento:____________________</td>
                        </tr>
                        <tr>
                            <td>Condicion Operación:____________________</td>
                            <td>{{$datos["DTE"]['resumen']['condicionOperacion']??''}}</td>
                        </tr>
                        <tr>
                            <td colspan="2">Observaciones:</td>
                        </tr>
                    </table>
                </td>
                <td style="width: 10%">Total Operaciones:
                    <table style="width: 100%">
                        <tr>
                            <td>Total No Sujeto:</td>
                            <td>${{ number_format(0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Exento:</td>
                            <td>${{ number_format(0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Gravadas:</td>
                            <td>${{ number_format($datos->sale_total, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Subtotal:</td>
                            <td>${{ number_format($datos->sale_total, 2) }}</td>
                        </tr>

                        <tr style="background-color: #57595B; color: white;">
                            <td>
                                <b>TOTAL A PAGAR:</b></td>
                            <td> ${{number_format($datos->sale_total, 2)}}
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>


</div>
</body>
</html>
