<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura Electrónica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .header {
            background-color: #111111;
            color: white;
            text-align: center;
            padding: 20px 10px;
        }
        .header img {
            max-width: 150px;
        }
        .header h1 {
            font-size: 20px;
            margin: 10px 0 5px;
        }
        .header p {
            font-size: 16px;
            margin: 0;
        }
        .body {
            padding: 20px;
            color: #333333;
        }
        .body h2 {
            font-size: 18px;
            color: #d32f2f;
        }
        .body p {
            margin: 10px 0;
            line-height: 1.6;
        }
        .body p strong {
            color: #d32f2f;
        }
        .footer {
            background-color: #f0f0f0;
            padding: 15px 20px;
            font-size: 12px;
            color: #555555;
            text-align: center;
        }
        .footer p {
            margin: 5px 0;
        }
        .footer a {
            color: #d32f2f;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="email-container">
    <!-- Header -->
    <div class="header">
        @php
            $logoPath = $sale->wherehouse->logo ? storage_path('app/public/' . $sale->wherehouse->logo) : null;
        @endphp
        @if($logoPath && file_exists($logoPath))
            <img src="{{ $message->embed($logoPath) }}" alt="{{ $sale->wherehouse->company->name ?? '' }} - {{ $sale->wherehouse->name ?? '' }}">
        @endif
        <h1>FACTURA ELECTRÓNICA</h1>
        <p>Notificación de envío de DTE</p>
    </div>

    <!-- Body -->
    <div class="body">
{{--        <p>{{$sale}}</p>--}}
        <p>Estimado cliente <strong>{{$sale->customer->name??'' .' '.$sale->customer->last_name??''}}</strong>,</p>
        <p>¡Esperamos que estés teniendo un día excelente!</p>
        <p>Te adjuntamos con mucho gusto la <strong>factura electrónica</strong> correspondiente a tu compra.</p>
        <p>
            A continuación, te compartimos el código de generación: <strong>{{$sale->generationCode}}</strong>
            y sello de recepción número: <strong>{{$sale->receiptStamp??''}}</strong>que necesitas
            para realizar cualquier gestión relacionada con este documento en {{$sale->wherehouse->company->name.' - '.$sale->wherehouse->name}}.
        </p>
        <p>
            Si necesitas más información sobre tu factura código: <strong>{{$sale->generationCode}}</strong>
            o tienes alguna consulta, por favor no dudes en comunicarte con nosotros a través del
            <strong>{{$sale->wherehouse->phone??''}}</strong> o <a href="{{$sale->wherehouse->email??''}}">{{$sale->wherehouse->email??''}}</a>.
        </p>
        <p>¡Estaremos encantados de ayudarte!</p>
        <p>¡Gracias por confiar en nosotros para ofrecerte un servicio de calidad!</p>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>
            Favor no responder este correo electrónico ya que es generado de manera automática,
            para comunicarte con nosotros llámanos al <strong>{{$sale->wherehouse->phone??''}}</strong> o escríbenos a:
            <a href="{{$sale->wherehouse->email??''}}">{{$sale->wherehouse->email??''}}</a>
        </p>
    </div>
</div>
</body>
</html>
