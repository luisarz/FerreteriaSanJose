<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoja de Conteo de Inventario</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
        }

        .header {
            text-align: center;
            padding: 10px 0;
            border-bottom: 2px solid #1e6bb8;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 18px;
            color: #1e6bb8;
            margin-bottom: 5px;
        }

        .header .subtitle {
            font-size: 12px;
            color: #666;
        }

        .header .fecha {
            font-size: 10px;
            color: #888;
            margin-top: 5px;
        }

        .filters {
            background-color: #f5f5f5;
            padding: 8px 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 10px;
        }

        .filters span {
            margin-right: 20px;
        }

        .filters strong {
            color: #1e6bb8;
        }

        .category-header {
            background-color: #1e6bb8;
            color: white;
            padding: 6px 10px;
            font-size: 11px;
            font-weight: bold;
            margin-top: 15px;
            margin-bottom: 0;
            page-break-after: avoid;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table th {
            background-color: #e85d04;
            color: white;
            padding: 6px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #e85d04;
        }

        table th.center {
            text-align: center;
        }

        table td {
            padding: 5px;
            border: 1px solid #ddd;
            font-size: 9px;
            vertical-align: middle;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tbody tr:hover {
            background-color: #fff3cd;
        }

        .col-num {
            width: 5%;
            text-align: center;
        }

        .col-codigo {
            width: 15%;
            font-family: 'Courier New', monospace;
            font-size: 9px;
        }

        .col-producto {
            width: 40%;
        }

        .col-presentacion {
            width: 15%;
            text-align: center;
        }

        .col-conteo {
            width: 12.5%;
            text-align: center;
            background-color: #fffef0;
        }

        .conteo-box {
            border: 1px dashed #999;
            min-height: 20px;
            background-color: #fffef0;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 9px;
            color: #666;
        }

        .footer-info {
            display: flex;
            justify-content: space-between;
        }

        .signature-section {
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .signature-line {
            display: inline-block;
            width: 200px;
            border-top: 1px solid #333;
            text-align: center;
            padding-top: 5px;
            margin-right: 50px;
        }

        .page-break {
            page-break-after: always;
        }

        .summary {
            background-color: #e8f4fd;
            padding: 10px;
            margin-top: 15px;
            border-radius: 4px;
            border-left: 4px solid #1e6bb8;
        }

        .summary strong {
            color: #1e6bb8;
        }

        @page {
            margin: 15mm 10mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>HOJA DE CONTEO DE INVENTARIO</h1>
        <div class="subtitle">
            @if($categoryName && $marcaName)
                Categoría: {{ $categoryName }} | Marca: {{ $marcaName }}
            @elseif($categoryName)
                Categoría: {{ $categoryName }}
            @elseif($marcaName)
                Marca: {{ $marcaName }}
            @else
                Todos los productos
            @endif
        </div>
        <div class="fecha">Generado: {{ $fecha }} | Total productos: {{ $totalProductos }}</div>
    </div>

    <div class="filters">
        <span><strong>Instrucciones:</strong> Anote la cantidad física encontrada en las columnas de conteo.</span>
    </div>

    @foreach($groupedInventories as $categoryName => $inventories)
        <div class="category-header">
            {{ $categoryName }} ({{ $inventories->count() }} productos)
        </div>

        <table>
            <thead>
                <tr>
                    <th class="col-num center">#</th>
                    <th class="col-codigo">CÓDIGO / BARRAS</th>
                    <th class="col-producto">PRODUCTO</th>
                    <th class="col-presentacion center">PRESENTACIÓN</th>
                    <th class="col-conteo center">CONTEO 1</th>
                    <th class="col-conteo center">CONTEO 2</th>
                </tr>
            </thead>
            <tbody>
                @foreach($inventories as $index => $inventory)
                    <tr>
                        <td class="col-num">{{ $index + 1 }}</td>
                        <td class="col-codigo">
                            @if($inventory->product->bar_code)
                                {{ $inventory->product->bar_code }}
                            @else
                                <span style="color: #999;">COD-{{ $inventory->product->id }}</span>
                            @endif
                        </td>
                        <td class="col-producto">
                            <strong>{{ $inventory->product->name }}</strong>
                            @if($inventory->product->marca)
                                <br><small style="color: #666;">{{ $inventory->product->marca->nombre }}</small>
                            @endif
                        </td>
                        <td class="col-presentacion">
                            {{ $inventory->product->unitmeasurement->description ?? 'N/A' }}
                        </td>
                        <td class="col-conteo">
                            <div class="conteo-box"></div>
                        </td>
                        <td class="col-conteo">
                            <div class="conteo-box"></div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    <div class="summary">
        <strong>Resumen:</strong> {{ $totalProductos }} productos en {{ $groupedInventories->count() }} categorías
    </div>

    <div class="signature-section">
        <p style="margin-bottom: 30px;"><strong>Observaciones:</strong> _____________________________________________________________________________</p>
        <p style="margin-bottom: 40px;">_______________________________________________________________________________________________</p>

        <div>
            <span class="signature-line">Realizado por</span>
            <span class="signature-line">Supervisado por</span>
        </div>

        <p style="margin-top: 20px; font-size: 8px; color: #999;">
            Fecha de conteo: ____/____/________ | Hora inicio: ______:______ | Hora fin: ______:______
        </p>
    </div>

    <div class="footer">
        <p>Documento generado el {{ $fecha }} | Sistema de Inventarios</p>
    </div>
</body>
</html>
