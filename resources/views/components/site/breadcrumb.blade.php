@props(['items'])

{{-- Visual breadcrumb. The matching JSON-LD is built in the controllers (StructuredData). --}}
<nav aria-label="Você está em" {{ $attributes->merge(['class' => 'text-sm text-subtle']) }}>
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1">
        @foreach ($items as $item)
            <li class="flex items-center gap-2">
                @if (! $loop->first)
                    @svg('heroicon-o-chevron-right', 'size-3.5')
                @endif

                @if (! $loop->last && ! empty($item['url']))
                    <a href="{{ $item['url'] }}" class="hover:underline">{{ $item['name'] }}</a>
                @else
                    <span @if ($loop->last) aria-current="page" @endif class="{{ $loop->last ? 'text-ink' : '' }}">{{ $item['name'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>