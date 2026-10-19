@props(['title', 'description' => null])

<x-layouts.site :title="$title" :description="$description">
    <div class="mx-auto max-w-3xl px-4 py-10">
        <h1 class="text-3xl font-bold tracking-tight text-ink">{{ $title }}</h1>
        <p class="mt-1 text-sm text-subtle">Última atualização: {{ config('legal.updated_at') }}</p>

        <div class="mt-8 space-y-8 rounded-card bg-surface p-6 shadow-card sm:p-8">
            {{ $slot }}
        </div>
    </div>
</x-layouts.site>