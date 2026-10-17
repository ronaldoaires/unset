<x-layouts.account title="Downloads">
    <x-account.page-header
        title="Downloads"
        description="Famílias que você já baixou." />

    @if ($families->isEmpty())
        <x-ui.empty-state
            icon="arrow-down-tray"
            title="Nenhum download ainda"
            description="As famílias que você baixar aparecem aqui, para baixar de novo quando precisar.">
            <x-ui.button :href="route('revit.families.index')">Explorar famílias</x-ui.button>
        </x-ui.empty-state>
    @else
        <p class="text-sm text-muted">
            {{ number_format($families->total(), 0, ',', '.') }}
            {{ $families->total() === 1 ? 'família baixada' : 'famílias baixadas' }}
        </p>

        <ul class="mt-3 space-y-3">
            @foreach ($families as $family)
                {{-- Same rule as the family page: free, purchased or within the plan limit. --}}
                @php($access = \Illuminate\Support\Facades\Gate::inspect('download', $family))

                <li class="flex flex-col gap-4 rounded-card bg-surface p-4 shadow-card sm:flex-row sm:items-center">
                    <div class="relative aspect-[4/3] w-24 shrink-0 overflow-hidden rounded-control bg-canvas">
                        @if ($family->thumbnail_url)
                            <img
                                src="{{ $family->thumbnail_url }}"
                                alt="{{ $family->name }} - família Revit"
                                width="96"
                                height="72"
                                loading="lazy"
                                class="absolute inset-0 size-full object-contain"
                            >
                        @else
                            <span class="absolute inset-0 flex items-center justify-center text-subtle">
                                @svg('heroicon-o-cube', 'size-7')
                            </span>
                        @endif
                    </div>

                    <div class="min-w-0 flex-1">
                        <h2 class="truncate text-base font-semibold text-ink">
                            <a href="{{ route('revit.families.show', ['family' => $family->slug]) }}" class="hover:underline">{{ $family->name }}</a>
                        </h2>

                        <p class="mt-0.5 truncate text-xs text-subtle">
                            {{ collect([$family->category?->name, $family->brand?->name])->filter()->implode(' · ') }}
                        </p>

                        <p class="mt-1.5 text-sm text-muted">
                            Último download em {{ $family->last_downloaded_at->format('d/m/Y \à\s H:i') }}
                            · {{ $family->user_downloads_count }}
                            {{ $family->user_downloads_count === 1 ? 'download' : 'downloads' }}
                        </p>
                    </div>

                    <div class="sm:w-64 sm:shrink-0">
                        @if ($access->allowed())
                            <x-ui.button :href="route('revit.families.download', ['family' => $family->slug])" class="w-full">
                                @svg('heroicon-o-arrow-down-tray', 'size-4')
                                Baixar novamente
                            </x-ui.button>
                        @else
                            <x-ui.alert>{{ $access->message() }}</x-ui.alert>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        {{ $families->links('site.pagination') }}
    @endif
</x-layouts.account>