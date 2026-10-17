@props(['title', 'description' => null])

<div class="mb-6">
    <h1 class="text-2xl font-bold leading-tight tracking-tight text-ink">{{ $title }}</h1>

    @if ($description)
        <p class="mt-1 text-sm text-muted">{{ $description }}</p>
    @endif
</div>