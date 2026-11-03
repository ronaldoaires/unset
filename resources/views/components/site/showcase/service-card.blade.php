@props(['service'])

@php
$url = route('services.show', ['area' => $service->area->slug(), 'service' => $service->slug]);
@endphp

{{-- Whole card clickable through the stretched link on the title. --}}
<article class="group/card relative flex flex-col rounded-card bg-surface p-6 shadow-card ring-1 ring-line transition has-[.card-link:hover]:-translate-y-0.5 has-[.card-link:hover]:shadow-lg">
    <span class="flex size-12 items-center justify-center rounded-control bg-primary-soft text-primary">
        @svg('heroicon-o-' . $service->icon_name, 'size-6')
    </span>

    <h3 class="mt-4 text-lg font-bold leading-tight text-ink">
        <a href="{{ $url }}" class="card-link after:absolute after:inset-0 after:rounded-card focus:outline-none focus-visible:after:ring-2 focus-visible:after:ring-primary">
            {{ $service->name }}
        </a>
    </h3>

    <p class="mt-2 flex-1 text-sm leading-relaxed text-muted">{{ $service->summary }}</p>

    <span aria-hidden="true" class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-primary">
        Saiba mais
        @svg('heroicon-o-arrow-right', 'size-4 transition group-has-[.card-link:hover]/card:translate-x-1')
    </span>
</article>
