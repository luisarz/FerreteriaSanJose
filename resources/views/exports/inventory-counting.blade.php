<table>
    <thead>
        <tr>
            <th colspan="7" style="font-size: 16px; font-weight: bold; text-align: center;">
                HOJA DE CONTEO DE INVENTARIO
            </th>
        </tr>
        <tr>
            <th colspan="7" style="text-align: center;">
                Sucursal: {{ $branchName }} | Filtro: {{ $searchTerm }} | {{ $fecha }} | Total: {{ number_format($totalProductos) }} productos
            </th>
        </tr>
        <tr style="background-color: #E85D04; color: white; font-weight: bold;">
            <th style="width: 5%;">#</th>
            <th style="width: 12%;">CÓDIGO</th>
            <th style="width: 35%;">PRODUCTO</th>
            <th style="width: 10%;">PRES.</th>
            <th style="width: 10%;">STOCK</th>
            <th style="width: 14%;">CONTEO 1</th>
            <th style="width: 14%;">CONTEO 2</th>
        </tr>
    </thead>
    <tbody>
        @php $globalIndex = 1; @endphp
        @foreach($groupedInventories as $categoryName => $items)
            <tr style="background-color: #1E6BB8; color: white; font-weight: bold;">
                <td colspan="7">{{ $categoryName ?? 'Sin Categoría' }} ({{ $items->count() }})</td>
            </tr>
            @foreach($items as $inv)
                <tr>
                    <td style="text-align: center;">{{ $globalIndex++ }}</td>
                    <td>{{ $inv->bar_code ?: 'COD-'.$inv->product_id }}</td>
                    <td>{{ $inv->product_name }}@if($inv->marca_name) ({{ $inv->marca_name }})@endif</td>
                    <td style="text-align: center;">{{ $inv->unit_description ?? 'N/A' }}</td>
                    <td style="text-align: center;">{{ number_format($inv->stock, 2) }}</td>
                    <td style="background-color: #FFFEF0;"></td>
                    <td style="background-color: #FFFEF0;"></td>
                </tr>
            @endforeach
        @endforeach
    </tbody>
</table>
