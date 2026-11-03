@props(['project', 'heading' => 'h3'])

{{-- Whole card clickable through the stretched link on the title. --}}
<article class="group/card relative flex flex-col overflow-hidden rounded-card bg-surface shadow-card transition has-[.card-link:hover]:-translate-y-0.5 has-[.card-link:hover]:shadow-lg">
    {{-- Fixed ratio box, so portrait and landscape covers line up. --}}
    <div class="relative aspect-[4/3] overflow-hidden bg-canvas">
        <img
            src="{{ $project->cover_thumb_url }}"
            alt="{{ $project->title }}"
            width="400"
            height="300"
            loading="lazy"
            class="absolute inset-0 size-full object-cover transition duration-300 group-has-[.card-link:hover]/card:scale-105">
    </div>

    <div class="flex flex-1 flex-col gap-2 p-4">
        <span class="w-fit rounded-full bg-primary-soft px-2.5 py-1 text-xs font-semibold text-on-primary-soft">
            {{ $project->service->name }}
        </span>

        <{{ $heading }} class="line-clamp-2 font-semibold leading-tight text-ink">
            <a
                href="{{ route('portfolio.show', ['project' => $project->slug]) }}"
                class="card-link after:absolute after:inset-0 focus:outline-none focus-visible:after:ring-2 focus-visible:after:ring-primary">
                {{ $project->title }}
            </a>
        </{{ $heading }}>

        @if ($project->location || $project->year)
        <p class="mt-auto flex items-center gap-1.5 text-xs text-subtle">
            @svg('heroicon-o-map-pin', 'size-4')
            {{ collect([$project->location, $project->year])->filter()->join(' · ') }}
        </p>
        @endif
    </div>
</article>
