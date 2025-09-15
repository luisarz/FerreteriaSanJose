<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
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
            left: 0;
            width: 100%;
            border: 1px solid black;
            text-align: right;
            font-size: 11px;
            padding: 1;
        }
        .content {
            flex: 1;
            padding-bottom: 100px;
        }
        .header img {
            width: 100px;
        }
        .empresa-info,
        .documento-info,
        .tabla-productos,
        .resumen {
            margin: 10px 0;
        }
        .tabla-productos th,
        .tabla-productos td {
            padding: 5px;
        }
        .resumen p {
            margin: 5px 0;
            text-align: right;
        }
        .table {
            width: 100%;
            border: 1px solid black;
        }
        tfoot {
            border: 2px solid black;
        }
        tfoot tr {
            border-top: 2px solid black;
            border-bottom: 2px solid black;
        }
        .sale-block {
            page-break-inside: avoid;
            break-inside: avoid;
            -webkit-region-break-inside: avoid;
        }
    </style>
</head>
<body>
<div class="header">
    <table style="text-align: left; border:1px solid black; border-radius: 10px; width: 100%;">
        <tr>
            <td colspan="4" style="text-align: center;">
                <h2>{{$empresa->name}} | {{$sucursal->name}}</h2>
                <h4>REPORTE DE COMISIÓN DE VENTAS Desde: {{date('d-m-Y',strtotime($startDate))}} - Hasta {{date('d-m-Y',strtotime($endDate))}}</h4>
                <h4>Vendedor: {{ strtoupper($empleado->name.' '.$empleado->lastname) }}</h4>
            </td>
        </tr>
    </table>

    @php
        $grandTotal = 0;
        $manoObraTotal = 0;
        $categoryTotals = [];
        $parentCategoryTotals = [];
        $childToParent = [];
    @endphp

    @foreach ($productsByDayAndSale as $day => $sales)
        <h2 class="text-xl font-semibold mt-6 mb-2">Fecha: {{ $day }}</h2>

        @foreach ($sales as $saleId => $items)
            @php
                $first = $items->first();
                $saleTotal = $items->sum('total_item_with_discount');
                $grandTotal += $saleTotal;

                foreach ($items as $item) {
                    $cat = $item->category_name ?? 'Sin categoría';
                    $parentCat = $item->parent_category_name ?? 'Sin categoría padre';

                    $categoryTotals[$cat] = ($categoryTotals[$cat] ?? 0) + $item->total_item_with_discount;
                    $parentCategoryTotals[$parentCat] = ($parentCategoryTotals[$parentCat] ?? 0) + $item->total_item_with_discount;
                    $childToParent[$cat] = $parentCat;

                    if ($parentCat === 'MANO DE OBRA') {
                        $manoObraTotal += $item->total_item_with_discount;
                    }
                }
            @endphp

            <div class="mb-4 p-4 rounded border sale-block">
                <div class="mb-3 text-sm">
                    <h3>
                        Venta ID: {{ $saleId }} &nbsp;&nbsp;
                        Factura: {{ $first->numero_factura ?? '—' }} &nbsp;&nbsp;
                        Orden: {{ $first->numero_orden ?? '—' }} <br>
                        Vendedor: {{ $first->seller_name ?? 'N/A' }} &nbsp;&nbsp;
                        Mecánico: {{ $first->mechanic_name ?? 'N/A' }}
                    </h3>
                </div>

                <table class="min-w-full border border-gray-300 text-sm" style="width: 100%; border: 1px solid black;">
                    <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Categoría (Hija)</th>
                        <th>Categoría (Padre)</th>
                        <th>Cantidad</th>
                        <th>Precio</th>
                        <th>Descuento</th>
                        <th>Total sin descuento</th>
                        <th>Total con descuento</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($items as $item)
                        @php
                            $isNoParentCategory = ($item->parent_category_name === 'Sin categoría padre');
                            $rowStyle = $isNoParentCategory ? 'color: red;' : '';
                        @endphp
                        <tr style="border-bottom: 1px solid #e5e7eb; {{ $rowStyle }}">
                            <td>{{ $item->product_name }} ({{ $item->sku }})</td>
                            <td>{{ $item->category_name }}</td>
                            <td>{{ $item->parent_category_name }}</td>
                            <td style="text-align: right;">{{ $item->quantity }}</td>
                            <td style="text-align: right;">${{ number_format($item->price, 2) }}</td>
                            <td style="text-align: right;">{{ $item->discount }}%</td>
                            <td style="text-align: right;">${{ number_format($item->total_after_discount, 2) }}</td>
                            <td style="text-align: right; font-weight: 600; color: #15803d;">${{ number_format($item->total_item_with_discount, 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                    <tr style="background-color: #f3f4f6; font-weight: 700;">
                        <td colspan="7" style="text-align: right;">Total Venta:</td>
                        <td style="text-align: right; color: #1e40af;">${{ number_format($saleTotal, 2) }}</td>
                    </tr>
                    </tfoot>
                </table>
            </div>
        @endforeach
    @endforeach

    <hr style="margin: 2rem 0; border-color: #d1d5db;" />

    <h3 style="font-weight: 700;">Subtotales por Categoría (Hija)</h3>
    <table style="width: 100%; max-width: 600px; border-collapse: collapse; border: 1px solid #d1d5db; margin-bottom: 1.5rem;">
        <thead style="background-color: #f3f4f6; color: #374151;">
        <tr>
            <th style="border: 1px solid #d1d5db; padding: 6px;">Categoría Padre</th>
            <th style="border: 1px solid #d1d5db; padding: 6px;">Categoría Hija</th>
            <th style="border: 1px solid #d1d5db; padding: 6px; text-align: right;">Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($categoryTotals as $category => $subtotal)
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 6px;">{{ $childToParent[$category] ?? 'Sin categoría padre' }}</td>
                <td style="border: 1px solid #d1d5db; padding: 6px;">{{ $category }}</td>
                <td style="border: 1px solid #d1d5db; padding: 6px; text-align: right; font-weight: 600; color: #1e40af;">
                    ${{ number_format($subtotal, 2) }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3 style="font-weight: 700;">Subtotales por Categoría Padre</h3>
    <table style="width: 100%; max-width: 400px; border-collapse: collapse; border: 1px solid #d1d5db; margin-bottom: 2rem;">
        <thead style="background-color: #f3f4f6; color: #374151;">
        <tr>
            <th style="border: 1px solid #d1d5db; padding: 6px;">Categoría Padre</th>
            <th style="border: 1px solid #d1d5db; padding: 6px; text-align: right;">Subtotal</th>
        </tr>
        </thead>
        <tbody>
        @php
            $manoObraTotalss = 0;
        @endphp
        @foreach ($parentCategoryTotals as $parentCategory => $subtotal)
            @if(trim($parentCategory) === "MANO DE OBRA")
                @php $manoObraTotalss += $subtotal; @endphp
            @endif
            <tr>
                <td style="border: 1px solid #d1d5db; padding: 6px;">{{ $parentCategory }}</td>
                <td style="border: 1px solid #d1d5db; padding: 6px; text-align: right; font-weight: 600; color: #1e40af;">
                    ${{ number_format($subtotal, 2) }}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>


    @php
        $netTotal = $grandTotal - $manoObraTotalss;
    @endphp

    <h3 style="text-align: right; font-weight: 700; color: #1e40af;">Total Ventas: ${{ number_format($grandTotal, 2) }}</h3>
    <h3 style="text-align: right; font-weight: 700; color: #b45309;">Total Mano de Obra: ${{ number_format($manoObraTotalss, 2) }}</h3>
    <h2 style="text-align: right; font-weight: 700; color: #15803d;">Total Neto (Ventas - Mano de Obra): ${{ number_format($netTotal, 2) }}</h2>

    <br><br>
    <p style="text-align: left">F:Recibido: _____________________________</p>
</div>
</body>
</html>
