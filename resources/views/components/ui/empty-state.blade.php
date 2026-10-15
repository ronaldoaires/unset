@props(['title', 'description' => null, 'icon' => 'inbox'])

<div {{ $attributes->merge(['class' => 'rounded-card bg-surface px-6 py-12 text-center shadow-card']) }}>
    <span class="mx-auto flex size-14 items-center justify-center rounded-full bg-primary-soft text-primary">
        @svg('heroicon-o-' . $icon, 'size-7')
    </span>

    <h2 class="mt-4 text-lg font-semibold text-ink">{{ $title }}</h2>

    @if ($description)
        <p class="mx-auto mt-1 max-w-prose text-sm text-muted">{{ $description }}</p>
    @endif

    {{-- The slot holds the call to action (an empty state should always suggest a next step). --}}
    @if (! $slot->isEmpty())
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>