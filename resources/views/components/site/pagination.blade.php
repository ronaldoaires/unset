@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginação" class="mt-8 flex items-center justify-between gap-3">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="inline-flex min-h-12 items-center rounded-control border border-line px-5 text-sm font-semibold text-subtle opacity-60">Anterior</span>
        @else
            <x-ui.button :href="$paginator->previousPageUrl()" variant="outline" rel="prev">Anterior</x-ui.button>
        @endif

        <span class="text-sm text-muted">Página {{ $paginator->currentPage() }} de {{ $paginator->lastPage() }}</span>

        @if ($paginator->hasMorePages())
            <x-ui.button :href="$paginator->nextPageUrl()" variant="outline" rel="next">Próxima</x-ui.button>
        @else
            <span aria-disabled="true" class="inline-flex min-h-12 items-center rounded-control border border-line px-5 text-sm font-semibold text-subtle opacity-60">Próxima</span>
        @endif
    </nav>
@endif