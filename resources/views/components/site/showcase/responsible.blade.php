@php
$company     = config('showcase.company');
$responsible = config('showcase.responsible');
@endphp

{{-- "Quem faz": in engineering, the professional who signs the project weighs on the decision. --}}
<section {{ $attributes->merge(['class' => 'rounded-card bg-surface p-6 shadow-card sm:p-8']) }}>
    <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
        <span class="flex size-16 shrink-0 items-center justify-center rounded-full bg-primary text-2xl font-bold text-on-primary" aria-hidden="true">
            {{ collect(explode(' ', $responsible['name']))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->join('') }}
        </span>

        <div class="flex-1">
            <p class="text-sm font-semibold uppercase tracking-wide text-primary">Quem faz</p>
            <h2 class="mt-1 text-xl font-bold text-ink">
                {{ $company['name'] }} <span class="font-medium text-muted">- {{ mb_strtolower($responsible['role']) }} {{ $responsible['name'] }}</span>
            </h2>
            <p class="mt-1 text-sm font-semibold text-ink">{{ $responsible['title'] }} · {{ $responsible['registry'] }}</p>

            <ul class="mt-4 grid gap-2 text-sm text-muted sm:grid-cols-2">
                @foreach ($responsible['credentials'] as $credential)
                <li class="flex items-start gap-2">
                    @svg('heroicon-o-check-badge', 'mt-0.5 size-5 shrink-0 text-primary')
                    {{ $credential }}
                </li>
                @endforeach
            </ul>

            <div class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                <span class="inline-flex items-center gap-1.5 text-muted">
                    @svg('heroicon-o-globe-americas', 'size-5 text-primary')
                    {{ $company['service_mode'] }}
                </span>

                @foreach ($responsible['links'] as $label => $url)
                <a href="{{ $url }}" target="_blank" rel="noopener me" class="font-semibold text-primary hover:underline">{{ $label }}</a>
                @endforeach
            </div>
        </div>
    </div>
</section>
