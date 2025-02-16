<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Datos de Apertura</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            /*background-color: #f9f9f9;*/
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
        }

        .section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #f5c2c7;
            border-radius: 5px;
            /*background-color: #fff5f5;*/
        }

        .section-title {
            display: flex;
            align-items: center;
            font-weight: bold;
            margin-bottom: 15px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .totals {
            font-size: 18px;
            font-weight: bold;
        }

        .totals span {
            color: #007bff;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="section-title">
        {{$empresa->name}} <br>
         Corte de Caja   <br>
        Fecha Impresión {{date('d-m-Y H:i:s')}}
    </div>
    <!-- Datos de apertura -->
    <div class="section">
        <div class="section-title">
            Datos de apertura
        </div>
        <div class="form-group">
            <table>
                <tr>
                    <td>Caja</td>
                    <td><b>{{$caja->cashbox->description}}</b></td>
                </tr>
                <tr>
                    <td>Fecha Apertura</td>
                    <td>{{$caja->created_at}}</td>
                </tr>
                <tr>
                    <td>Monto Apertura</td>
                    <td>$<b> {{number_format($caja->open_amount, 2)}}</b></td>
                </tr>
                <tr>
                    <td>Empleado</td>
                    <td>{{$caja->openEmployee->name}} {{$caja->openEmployee->lastname}}</td>
                </tr>
            </table>

        </div>

    </div>

    <!-- Ingresos y Egresos -->
    <div class="section">
        <div class="section-title">
            Operaciones
        </div>
        <table>
            <thead>
            <tr>
                <th>Ingresos</th>
                <th>Egresos</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <table>
                        <tr>
                            <td>Facturacion</td>
                            <td>$<b>{{number_format($caja->saled_amount, 2)}}</b></td>
                        </tr>
                        <tr>
                            <td>Ordenes</td>
                            <td>$<b>{{number_format($caja->ordered_amount, 2)}}</b></td>
                        </tr>
                        <tr>
                            <td>Caja Chica</td>
                            <td>$<b>{{number_format($caja->in_cash_amount, 2)}}</b></td>
                        </tr>
                    </table>
                </td>
                <td>
                    <p>Caja Chica: <span class="totals">$0.00</span></p>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <!-- Cierre -->
    <div class="section">
        <div class="section-title">
            Cierre
        </div>
        <table>

            <tr>
                <td>Fecha Cierre</td>
                <td>{{$caja->updated_at}}</td>
            </tr>
            <tr>
                <td>Monto Cierre</td>
                <td>$<b> {{number_format($caja->closed_amount, 2)}}</b></td>
            </tr>
            <tr>
                <td>Empleado</td>
                <td>{{$caja->closeEmployee->name}} {{$caja->closeEmployee->lastname}}</td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
