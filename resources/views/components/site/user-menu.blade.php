@guest
    <div class="flex items-center gap-2">
        <a href="{{ route('login') }}" class="hidden min-h-12 items-center rounded-control px-3 text-sm font-medium hover:bg-on-header/10 sm:inline-flex">
            Entrar
        </a>
        <x-ui.button :href="route('register')" variant="inverse">Criar conta</x-ui.button>
    </div>
@endguest

@auth
    {{-- <details> keeps the menu working without any JavaScript. --}}
    <details class="relative">
        <summary class="flex min-h-12 cursor-pointer list-none items-center gap-2 rounded-control px-3 text-sm font-medium hover:bg-on-header/10 [&::-webkit-details-marker]:hidden">
            @svg('heroicon-o-user-circle', 'size-6')
            <span class="hidden sm:inline">{{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}</span>
        </summary>

        <div class="absolute right-0 z-20 mt-2 w-64 rounded-card bg-surface py-2 text-ink shadow-card ring-1 ring-line">
            @if (auth()->user()->isAdmin())
                <a href="{{ url('/admin') }}" class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-canvas">
                    @svg('heroicon-o-cog-6-tooth', 'size-5 text-primary')
                    Painel administrativo
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 px-4 py-3 text-left text-sm hover:bg-canvas">
                    @svg('heroicon-o-arrow-right-on-rectangle', 'size-5 text-primary')
                    Sair
                </button>
            </form>
        </div>
    </details>
@endauth