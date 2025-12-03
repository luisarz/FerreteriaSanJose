<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Hoja de Conteo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9px; line-height: 1.2; }
        .header { text-align: center; padding: 8px 0; border-bottom: 2px solid #1e6bb8; margin-bottom: 10px; }
        .header h1 { font-size: 14px; color: #1e6bb8; }
        .header p { font-size: 10px; color: #666; margin-top: 3px; }
        .cat-header { background: #1e6bb8; color: white; padding: 4px 8px; font-size: 10px; font-weight: bold; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #e85d04; color: white; padding: 4px; font-size: 8px; text-align: left; border: 1px solid #e85d04; }
        th.c { text-align: center; }
        td { padding: 3px 4px; border: 1px solid #ddd; font-size: 8px; }
        tr:nth-child(even) { background: #f9f9f9; }
        .n { width: 4%; text-align: center; }
        .cod { width: 12%; font-family: monospace; }
        .prod { width: 34%; }
        .pres { width: 10%; text-align: center; }
        .stock { width: 8%; text-align: center; font-weight: bold; }
        .cont { width: 12%; background: #fffef0; }
        .box { border: 1px dashed #999; min-height: 14px; }
        .footer { margin-top: 15px; padding-top: 8px; border-top: 1px solid #ddd; font-size: 8px; }
        .sig { display: inline-block; width: 180px; border-top: 1px solid #333; text-align: center; padding-top: 3px; margin-right: 30px; margin-top: 25px; }
        @page { margin: 10mm 8mm; }
    </style>
</head>
<body>
<div class="header">
    <h1>HOJA DE CONTEO DE INVENTARIO</h1>
    <p>
        <strong>Sucursal:</strong> {{ $branchName }} | <strong>Filtro:</strong> {{ $searchTerm }} | {{ $fecha }} | <strong>Total:</strong> {{ number_format($totalProductos) }} productos
    </p>
</div>

@foreach($groupedInventories as $catName => $items)
<div class="cat-header">{{ $catName }} ({{ $items->count() }})</div>
<table>
    <thead>
        <tr>
            <th class="n c">#</th>
            <th class="cod">CÓDIGO</th>
            <th class="prod">PRODUCTO</th>
            <th class="pres c">PRES.</th>
            <th class="stock c">STOCK</th>
            <th class="cont c">CONTEO 1</th>
            <th class="cont c">CONTEO 2</th>
        </tr>
    </thead>
    <tbody>
    @foreach($items as $i => $inv)
        <tr>
            <td class="n">{{ $i + 1 }}</td>
            <td class="cod">{{ $inv->bar_code ?: 'COD-'.$inv->product_id }}</td>
            <td class="prod">{{ $inv->product_name }}@if($inv->marca_name) <small>({{ $inv->marca_name }})</small>@endif</td>
            <td class="pres">{{ $inv->unit_description ?? 'N/A' }}</td>
            <td class="stock">{{ number_format($inv->stock, 2) }}</td>
            <td class="cont"><div class="box"></div></td>
            <td class="cont"><div class="box"></div></td>
        </tr>
    @endforeach
    </tbody>
</table>
@endforeach

<div class="footer">
    <p><b>Observaciones:</b> _______________________________________________</p>
    <div><span class="sig">Realizado por</span><span class="sig">Supervisado por</span></div>
    <p style="margin-top:10px;">Fecha: ____/____/______</p>
</div>
</body>
</html>
