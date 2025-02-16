@extends('filament::layouts.app')

@section('content')
    <div class="p-4">
        <h1 class="text-xl font-semibold">Listado de Ventas</h1>

        <x-filament::table>
            <x-filament::tables.actions>
                Tables\Actions\Action::make('Historial')
                ->label('Historial DTE')
                ->icon('heroicon-o-document')
                ->visible(fn ($record) => $record->is_dte)
                ->action(function ($record, $livewire) {
                $historial = HistoryDte::where('sales_invoice_id', $record->id)->get();
                $livewire->dispatchBrowserEvent('show-historial-modal', [
                'historial' => $historial->toArray(),
                ]);
                })
            </x-filament::tables.actions>
        </x-filament::table>

        @include('components.dte-history-modal')
    </div>
@endsection
