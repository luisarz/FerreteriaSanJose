@if (session('branch_logo'))
    <img src="{{ asset(session('branch_logo')) }}" alt="Logo de la Sucursal" style="max-height: 40px;">
@endif