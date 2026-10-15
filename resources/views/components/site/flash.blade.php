@if (session('status'))
    <div class="mx-auto mt-4 w-full max-w-6xl px-4">
        <div class="rounded-control bg-green-50 p-3 text-sm text-green-800" role="status">
            {{ session('status') }}
        </div>
    </div>
@endif