{{-- "Como funciona": the same short path for every service, to lower the barrier to ask. --}}
<section {{ $attributes }}>
    <h2 class="text-2xl font-bold tracking-tight text-ink">Como funciona</h2>

    <ol class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach (config('showcase.steps') as $step)
        <li class="rounded-card bg-surface p-5 shadow-card">
            <span class="flex size-9 items-center justify-center rounded-full bg-primary text-sm font-bold text-on-primary">{{ $loop->iteration }}</span>
            <h3 class="mt-3 font-semibold text-ink">{{ $step['title'] }}</h3>
            <p class="mt-1 text-sm leading-relaxed text-muted">{{ $step['text'] }}</p>
        </li>
        @endforeach
    </ol>
</section>
