@php($items = collect(config('site.nav'))->where('enabled', true)->where('bottom', true))

{{-- Mobile only: primary destinations stay inside the thumb zone. --}}
<nav
    aria-label="Navegação rápida"
    class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface pb-[env(safe-area-inset-bottom)] md:hidden">
    <ul class="mx-auto flex max-w-md items-stretch justify-around">
        @foreach ($items as $item)
        @php($active = request()->routeIs($item['active'] ?? $item['route']))
        <li class="flex-1">
            <a
                href="{{ route($item['route']) }}"
                @if ($active) aria-current="page" @endif
                class="flex min-h-16 flex-col items-center justify-center gap-1 text-xs font-medium {{ $active ? 'text-primary' : 'text-subtle' }}">
                <span class="rounded-full px-5 py-1 {{ $active ? 'bg-primary-soft' : '' }}">
                    @svg('heroicon-o-' . $item['icon'], 'size-6')
                </span>
                {{ $item['label'] }}
            </a>
        </li>
        @endforeach

        {{-- Last slot: login for guests. Logged-in users get an account item when the user area exists. --}}
        @guest
        <li class="flex-1">
            <a
                href="{{ route('login') }}"
                class="flex min-h-16 flex-col items-center justify-center gap-1 text-xs font-medium text-subtle">
                <span class="rounded-full px-5 py-1">
                    @svg('heroicon-o-user-circle', 'size-6')
                </span>
                Entrar
            </a>
        </li>
        @endguest
        @auth
        @php($accountActive = request()->routeIs('account.*'))
        <li class="flex-1">
            <a
                href="{{ route('account.index') }}"
                @if ($accountActive) aria-current="page" @endif
                class="flex min-h-16 flex-col items-center justify-center gap-1 text-xs font-medium {{ $accountActive ? 'text-primary' : 'text-subtle' }}">
                <span class="rounded-full px-5 py-1 {{ $accountActive ? 'bg-primary-soft' : '' }}">
                    @svg('heroicon-o-user-circle', 'size-6')
                </span>
                Conta
            </a>
        </li>
        @endauth
    </ul>
</nav>