@props(['name', 'label'])

<div>
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-ink">{{ $label }}</label>

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        {{ $attributes->merge([
            'class' => 'block min-h-12 w-full rounded-control border border-line bg-surface px-3.5 py-2 text-base text-ink shadow-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20',
        ]) }}
    >
        {{ $slot }}
    </select>
</div>