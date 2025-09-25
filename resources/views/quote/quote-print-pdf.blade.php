<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.4;
        }

        .header {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 2px solid #004080;
            background-color: #f9f9f9;
            gap: 20px;
        }

        .header img {
            max-height: 80px;
            object-fit: contain;
        }

        .company-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .company-info h2 {
            margin: 0;
            color: #004080;
            font-size: 18px;
        }

        .company-info h3 {
            margin: 2px 0 0 0;
            font-size: 14px;
            color: #555;
        }

        .saludo {
            margin: 15px 20px;
            font-size: 13px;
        }

        .customer-info, .document-info {
            margin: 15px 20px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            font-size: 13px;
        }

        .table {
            width: 95%;
            margin: 10px auto;
            border-collapse: collapse;
            font-size: 12px;
        }

        .table th, .table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        .table th {
            background-color: #004080;
            color: #fff;
        }

        .table td {
            background-color: #fdfdfd;
        }

        .table-anulado::before {
            content: "ANULADO";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            font-weight: bold;
            color: rgba(0,0,0,0.05);
            z-index: 0;
            pointer-events: none;
        }

        .footer {
            margin-top: 20px;
            padding: 15px 20px;
            border-top: 2px solid #004080;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            /*background-color: #f9f9f9;*/
        }

        .footer table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer td {
            padding: 3px 5px;
        }

        .footer .total-box td {
            border: 1px solid #004080;
            padding: 5px;
        }

        .footer .total-box tr:first-child td {
            background-color: #004080;
            color: white;
        }

        .resumen-letras {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .despedida {
            margin: 15px 20px;
            font-size: 13px;
        }
    </style>
</head>
<body>

<!-- HEADER: Logo y Empresa lado a lado -->
<!-- HEADER: Logo y Empresa lado a lado usando tabla -->
<table width="100%" style="border-bottom: 2px solid #004080;  margin-bottom: 15px;">
    <tr>
        <td style="width: 20%;">
            <img src="{{ asset($logo_url ?? '') }}" alt="Logo Empresa" style="max-height:80px; object-fit:contain;">
        </td>
        <td style="width: 80%; text-align:right;">
            <h2 style="margin:0; color:#004080;">{{ $empresa->name }} | {{ $datos->whereHouse->name }} | <b>{{ $datos->whereHouse->phone }}</b> </h2>
            <h3 style="margin:2px 0 0 0; font-size:14px; color:#555;">
                COTIZACIÓN | <b>{{ $datos->order_number }}</b> | {{ date('d-m-Y H:i:s', strtotime($datos->created_at)) }}
            </h3>
        </td>
    </tr>
</table>


<!-- SALUDO PERSONALIZADO -->
<div class="saludo">
    <p>Estimado/a <b>{{ $datos->customer->name ?? '' }} {{ $datos->customer->last_name ?? '' }}</b>,</p>
    <p>Reciba un cordial saludo de nuestra empresa, al mismo tiempo deseándole éxitos en sus labores diarias. Me agrada
        presentarle nuestra cotizacion sobre los siguientes prodúctos:</p>
</div>

<!-- CLIENTE Y DETALLES -->
<div class="customer-info">
    <div><b>Estado:</b> {{ $datos->sale_status ?? '' }}</div>
    <div><b>Vendedor:</b> {{ $datos->seller->name ?? '' }} {{ $datos->seller->last_name ?? '' }}</div>
    <div><b>Cliente:</b> {{ $datos->customer->name ?? '' }} {{ $datos->customer->last_name ?? '' }}</div>
    <div><b>Dirección:</b> {{ $datos->customer->address ?? 'S/N' }}</div>
</div>

<!-- TABLA DE PRODUCTOS -->
<table class="table {{ $datos->sale_status == 'Anulado' ? 'table-anulado' : '' }}">
    <thead>
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
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td>{{ $item->quantity }}</td>
            <td>Unidad</td>
            <td>
                {{ $item->inventory->product->name ?? '' }}

                @if(!empty($item->description))
                    <br><b>DESCRIPCIÓN:</b><br>{{ $item->description ?? '' }}
                @endif
            </td>
            <td>${{ number_format($item->price ?? 0, 2) }}</td>
            <td>${{ number_format($item->total ?? 0, 2) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<!-- FOOTER -->
<div class="footer">
    <div style="width: 100%;">
        <div class="resumen-letras">VALOR EN LETRAS: {{ $montoLetras ?? '' }}</div>
        <table>
            <tr>
                <td>Entregado por:</td>
                <td>Recibido por:</td>
            </tr>
            <tr>
                <td>N° Documento:</td>
                <td>N° Documento:</td>
            </tr>
            <tr>
                <td>Condición Operación:</td>
                <td>{{ $datos["DTE"]['resumen']['condicionOperacion'] ?? '' }}</td>
            </tr>
            <tr>
                <td colspan="2">Observaciones: <b>{{$datos->observaciones??''}}</b></td>
            </tr>
        </table>
    </div>

    <div style="width: 30%;">
        <table class="total-box">
            <tr>
                <td colspan="2">TOTAL A PAGAR</td>
            </tr>
            <tr>
                <td>Total Gravadas:</td>
                <td>${{ number_format($datos->sale_total, 2) }}</td>
            </tr>
            <tr>
                <td>Subtotal:</td>
                <td>${{ number_format($datos->sale_total, 2) }}</td>
            </tr>
            <tr>
                <td><b>TOTAL</b></td>
                <td><b>${{ number_format($datos->sale_total, 2) }}</b></td>
            </tr>
        </table>
    </div>
</div>

<!-- DESPEDIDA -->
<div class="despedida">
{{--    <p>Gracias por su preferencia, <b>{{ $datos->customer->name ?? '' }}</b>.</p>--}}
    <p>Atentamente,</p>
    <p><b>{{ $empresa->name }}</b></p>
    <p>Agradecemos antemano su preferencia y esperamos servirle con la calidad que nos caracteriza. A espera de una
        respuesta favorable me suscribo de usted.</p>
</div>

</body>
</html>
