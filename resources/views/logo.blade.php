@if (auth()->check() && optional(auth()->user()->employee->wherehouse)->logo)
    <img src="{{ asset('storage/' . auth()->user()->employee->wherehouse->logo) }}" alt="Brand Logo" class="h-auto max-w-full">
@else
        <img src="{{ asset('storage/wherehouses/default-logo.png') }}" alt="Default Logo" class="max-w-full max-h-full ">

{{--        <lab>Cpmpiutec</lab>--}}
@endif
