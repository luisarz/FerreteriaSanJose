<div class="overflow-x-auto max-w-full">
    <table class="table-auto text-xs">
        <thead>
        <tr>
{{--            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Venta</th>--}}
            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Versión</th>
            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Ambiente</th>
            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Versión App</th>
            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Estado</th>
            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Código Generación</th>
            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Sello Recibido</th>
            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Fecha Procesamiento</th>
            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Clasifica Mensaje</th>
            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Código Mensaje</th>
            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Descripción Mensaje</th>
{{--            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">DTE</th>--}}
            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Observaciones</th>
        </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
        @foreach ($historial as $item)
            <tr style="{{$item['estado'] == 'RECHAZADO' ? 'background-color: rgb(254 202 202); ' : 'background-color: rgb(134 239 172);'}}">

{{--            <td class="px-4 py-2">{{$item["sales_invoice_id"]}}</td>--}}
                <td class="px-4 py-2">{{$item["version"]}}</td>
                <td class="px-4 py-2">{{$item["ambiente"]=="00"?'Prueba':'Produccion'}}</td>
                <td class="px-4 py-2">{{$item["versionApp"]}}</td>
                <td class="px-4 py-2">{{$item["estado"]}}</td>
                <td class="px-4 py-2">{{$item["codigoGeneracion"]}}</td>
                <td class="px-4 py-2">{{$item["selloRecibido"]}}</td>
                <td class="px-4 py-2">{{$item["fhProcesamiento"]}}</td>
                <td class="px-4 py-2">{{$item["clasificaMsg"]}}</td>
                <td class="px-4 py-2">{{$item["codigoMsg"]}}</td>
                <td class="px-4 py-2">{{$item["descripcionMsg"]}}</td>
{{--                <td class="px-4 py-2">--}}
{{--                    <button--}}
{{--                            class="text-blue-500 hover:underline"--}}
{{--                            onclick="showModal('{{ addslashes(json_encode($item['dte'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}')">--}}
{{--                        Ver DTE--}}
{{--                    </button>--}}
{{--                </td>--}}
                <td class="px-4 py-2">{{$item["observaciones"]}}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div id="dteModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center">
    <div class="bg-white p-6 rounded-lg w-1/2">
        <h2 class="text-lg font-bold mb-4">Detalle DTE</h2>
        <pre id="modalContent" class="bg-gray-100 p-4 rounded text-sm overflow-auto"></pre>
        <button
                class="mt-4 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600"
                onclick="closeModal()">
            Cerrar
        </button>
    </div>
</div>

<!-- Script para abrir y cerrar el modal -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        alert();
        // Aquí definimos la función showModal
        function showModal(content) {
            const modal = document.getElementById('dteModal');
            const modalContent = document.getElementById('modalContent');

            modal.classList.add('hidden'); // Asegúrate de cerrar el modal antes
            setTimeout(() => {
                modalContent.textContent = content;
                modal.classList.remove('hidden'); // Mostrar modal
            }, 100);
        }

        // Hacer visible la función en el ámbito global
        window.showModal = showModal;
    });
</script>
