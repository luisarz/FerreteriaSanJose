<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corte de Caja</title>
    <style>
        :root {
            --primary-color: #1e3a8a;
            --accent-color: #3b82f6;
            --light-bg:"";
            --border-color: #e5e7eb;
            --text-color: #111827;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-color);
            margin: 0;
            padding: 5px;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            border: 1px solid var(--border-color);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.03);
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            margin: 0;
            font-size: 20px;
            color: var(--primary-color);
        }

        .header small {
            color: gray;
            font-size: 14px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            border-left: 5px solid var(--accent-color);
            padding-left: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table th, table td {
            padding: 6px;
            border: 1px solid var(--border-color);
            text-align: left;
        }

        table th {
            background-color: var(--light-bg);
            font-weight: bold;
        }

        .totals {  font-size: 15px;

            font-weight: bold;
            background-color: #f0f4ff;
        }

        .currency {
            color: var(--accent-color);
            font-weight: bold;
            font-size: 15px
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>{{$empresa->name}} - Corte de Caja</h1>
        <small>Fecha de impresión: {{date('d-m-Y H:i:s')}}</small>
    </div>

    <div class="section">
        <div class="section-title">Datos de Apertura</div>
        <table>
            <tr><td>Caja</td><td>{{$caja->cashbox->description}}</td></tr>
            <tr><td>Fecha Apertura</td><td>{{ date('d-m-Y H:i:s', strtotime( $caja->created_at))}}</td></tr>
            <tr><td>Monto Apertura</td><td class="currency">${{number_format($caja->open_amount, 2)}}</td></tr>
            <tr><td>Empleado</td><td>{{$caja->openEmployee->name}} {{$caja->openEmployee->lastname}}</td></tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Operaciones</div>
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
                        <tr><td>Factura</td><td class="currency">${{number_format($caja->ingreso_factura, 2)}}</td></tr>
                        <tr><td>CCF</td><td class="currency">${{number_format($caja->ingreso_ccf, 2)}}</td></tr>
                        <tr><td>Ordenes</td><td class="currency">${{number_format($caja->ingreso_ordenes, 2)}}</td></tr>
                        <tr><td>Taller</td><td class="currency">${{number_format($caja->ingreso_taller, 2)}}</td></tr>
                        <tr><td>Caja Chica</td><td class="currency">${{number_format($caja->ingreso_caja_chica, 2)}}</td></tr>
                        <tr class="totals"><td>Total Ingresos</td><td class="currency">${{number_format($caja->ingreso_totales, 2)}}</td></tr>
                    </table>
                </td>
                <td>
                    <table>
                        <tr><td>Caja Chica</td><td class="currency">${{number_format($caja->egreso_caja_chica, 2)}}</td></tr>
                        <tr><td>Notas de Credito</td><td class="currency">${{number_format($caja->egreso_nc, 2)}}</td></tr>
                        <tr class="totals"><td>Total Egresos</td><td class="currency">${{number_format($caja->egresos_totales, 2)}}</td></tr>
                    </table>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Saldo de Cierre</div>
        <table>
            <tr><td>Efectivo Ventas</td><td class="currency">${{number_format($caja->saldo_efectivo_ventas, 2)}}</td></tr>
            <tr><td>Tarjetas</td><td class="currency">${{number_format($caja->saldo_tarjeta, 2)}}</td></tr>
            <tr><td>Cheque</td><td class="currency">${{number_format($caja->saldo_cheque, 2)}}</td></tr>
            <tr><td>Caja Chica</td><td class="currency">${{number_format($caja->saldo_caja_chica, 2)}}</td></tr>
            <tr><td>Efectivo Ordenes</td><td class="currency">${{number_format($caja->saldo_efectivo_ordenes, 2)}}</td></tr>
            <tr class="totals"><td>Ingresos Totales</td><td class="currency">${{number_format($caja->ingreso_totales, 2)}}</td></tr>
            <tr><td>- Egresos</td><td class="currency">-${{number_format($caja->saldo_egresos_totales, 2)}}</td></tr>
            <tr><td>+ Apertura</td><td class="currency">${{number_format($caja->open_amount, 2)}}</td></tr>
            <tr class="totals"><td>Saldo Total</td><td class="currency">${{number_format($caja->saldo_total_operaciones, 2)}}</td></tr>
            <tr><td>Fecha Cierre</td><td>{{ date('d-m-Y H:i:s', strtotime($caja->updated_at))}}</td></tr>
            <tr><td>Empleado</td><td>{{$caja->closeEmployee->name}} {{$caja->closeEmployee->lastname}}</td></tr>
        </table>
    </div>
</div>
</body>
</html>
