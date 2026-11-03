@props(['service' => null, 'project' => null, 'title' => 'Vamos tirar o seu projeto do papel?', 'text' => null])

<section {{ $attributes->merge(['class' => 'overflow-hidden rounded-card bg-header px-6 py-10 text-on-header shadow-card sm:px-10']) }}>
    <div class="max-w-2xl">
        <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ $title }}</h2>
        <p class="mt-3 text-on-header/85">
            {{ $text ?? 'Conte o que você precisa e receba uma proposta clara, sem compromisso. ' . config('showcase.company.service_mode') . '.' }}
        </p>
    </div>

    <x-site.showcase.cta-buttons :service="$service" :project="$project" inverse class="mt-6" />
</section>
