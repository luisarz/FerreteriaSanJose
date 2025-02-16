<!-- resources/views/components/breadcrumbs.blade.php -->
<nav class="flex items-center space-x-2">
    <ol class="flex text-sm text-gray-500">
        @foreach (request()->breadcrumbs() ?? [] as $breadcrumb)
            <li class="breadcrumb-item">
                <a href="{{ $breadcrumb->url }}" class="hover:text-blue-600">{{ $breadcrumb->title }}</a>
            </li>
        @endforeach
    </ol>
</nav>
