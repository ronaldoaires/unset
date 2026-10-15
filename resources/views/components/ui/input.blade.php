@props([
    'name',
    'label',
    'type' => 'text',
    'hint' => null,
])

<div>
    {{-- The label sits close to its field; the form spacing separates the fields. --}}
    <label for="{{ $name }}" class="mb-1.5 block text-sm font-medium text-ink">{{ $label }}</label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $attributes->get('value')) }}" @endif
        {{ $attributes->except('value')->merge([
            'class' => 'block min-h-12 w-full rounded-control border bg-surface px-3.5 py-2 text-base text-ink shadow-sm placeholder:text-subtle focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20 '
                . ($errors->has($name) ? 'border-red-500' : 'border-line'),
        ]) }}
    >

    @if ($hint && ! $errors->has($name))
        <p class="mt-1.5 text-xs text-subtle">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>