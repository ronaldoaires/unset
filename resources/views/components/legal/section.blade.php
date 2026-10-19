@props(['title'])

<section>
    <h2 class="text-lg font-semibold text-ink">{{ $title }}</h2>

    <div class="mt-2 space-y-3 text-sm leading-relaxed text-muted [&_a]:font-medium [&_a]:text-primary [&_a]:underline [&_strong]:text-ink [&_ul]:list-disc [&_ul]:space-y-1 [&_ul]:pl-5">
        {{ $slot }}
    </div>
</section>