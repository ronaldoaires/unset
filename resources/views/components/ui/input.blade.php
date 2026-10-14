@props([
    'name',
    'label',
    'type' => 'text',
    'hint' => null,
])

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-zinc-700">{{ $label }}</label>

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($name, $attributes->get('value')) }}" @endif
        {{ $attributes->except('value')->merge([
            'class' => 'mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500 '
                . ($errors->has($name) ? 'border-red-500' : 'border-zinc-300'),
        ]) }}
    >

    @if ($hint && ! $errors->has($name))
        <p class="mt-1 text-xs text-zinc-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>