@props([
    // App\Enums\ServiceArea: shows only that area's profile. Null shows both
    // side by side (hub and generic pages).
    'area' => null,
])

@php
$person   = config('showcase.person');
$company  = config('showcase.company');
$profiles = $area
    ? [$area->value => config('showcase.profiles.' . $area->value)]
    : config('showcase.profiles');
$initials = collect(explode(' ', $person['name']))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('');
@endphp

{{-- "Quem faz": each kind of client sees the credentials that matter to them
     (CREA for engineering, the developer background for technology). --}}
<section {{ $attributes->merge(['class' => 'rounded-card bg-surface p-6 shadow-card sm:p-8']) }}>
    <div class="flex items-center gap-4">
        <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-primary text-xl font-bold text-on-primary" aria-hidden="true">{{ $initials }}</span>
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-primary">Quem faz</p>
            <h2 class="text-xl font-bold text-ink">{{ $person['name'] }}</h2>
            @if (count($profiles) > 1)
            <p class="text-sm text-muted">{{ collect($profiles)->pluck('title')->join(' e ') }}</p>
            @else
            {{-- Single area: the title and its badge sit here, so the block below doesn't repeat them. --}}
            <p class="flex flex-wrap items-center gap-2 text-sm text-muted">
                {{ collect($profiles)->first()['title'] }}
                <span class="rounded-full bg-primary-soft px-2.5 py-0.5 text-xs font-semibold text-on-primary-soft">{{ collect($profiles)->first()['badge'] }}</span>
            </p>
            @endif
        </div>
    </div>

    <div @class(['mt-6 grid gap-6', 'lg:grid-cols-2' => count($profiles) > 1])>
        @foreach ($profiles as $profile)
        <div @class(['rounded-control bg-canvas p-5' => count($profiles) > 1])>
            @if (count($profiles) > 1)
            <div class="flex flex-wrap items-center gap-2">
                <h3 class="font-bold text-ink">{{ $profile['title'] }}</h3>
                <span class="rounded-full bg-primary-soft px-2.5 py-0.5 text-xs font-semibold text-on-primary-soft">{{ $profile['badge'] }}</span>
            </div>
            @endif
            <p @class(['text-sm text-muted', 'mt-1' => count($profiles) > 1])>{{ $profile['headline'] }}</p>

            <ul class="mt-4 grid gap-2 text-sm text-muted {{ count($profiles) > 1 ? '' : 'sm:grid-cols-2' }}">
                @foreach ($profile['credentials'] as $credential)
                <li class="flex items-start gap-2">
                    @svg('heroicon-o-check-badge', 'mt-0.5 size-5 shrink-0 text-primary')
                    {{ $credential }}
                </li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
        <span class="inline-flex items-center gap-1.5 text-muted">
            @svg('heroicon-o-globe-americas', 'size-5 text-primary')
            {{ $company['service_mode'] }}
        </span>

        @foreach ($person['links'] as $label => $url)
        <a href="{{ $url }}" target="_blank" rel="noopener me" class="font-semibold text-primary hover:underline">{{ $label }}</a>
        @endforeach
    </div>
</section>
