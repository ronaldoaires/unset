<x-layouts.account title="Minha conta">
    <x-account.page-header
        :title="'Olá, ' . \Illuminate\Support\Str::before($user->name, ' ')"
        description="Gerencie seus dados e acompanhe sua conta." />

    <div class="space-y-6">

        {{-- Alerts: only what needs the person's attention --}}
        @if ($deletionDate)
            <div class="rounded-card border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                Sua conta será encerrada em <strong>{{ $deletionDate->format('d/m/Y') }}</strong>.
                <a href="{{ route('account.danger') }}" class="font-medium underline">Cancelar pedido</a>
            </div>
        @endif

        @if ($user->pending_email)
            <div class="rounded-card border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                Troca de e-mail aguardando confirmação em <strong>{{ $user->pending_email }}</strong>.
                <a href="{{ route('account.email') }}" class="font-medium underline">Ver pedido</a>
            </div>
        @endif

        @unless ($hasPassword)
            <div class="rounded-card border border-line bg-surface p-4 text-sm text-muted shadow-card">
                Sua conta entra pelo Google. Se quiser também entrar com e-mail e senha,
                <a href="{{ route('account.password') }}" class="font-medium text-primary underline">crie uma senha</a>.
            </div>
        @endunless

        {{-- Plan --}}
        <section class="rounded-card bg-surface p-6 shadow-card">
            @if ($subscription)
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-subtle">{{ $hasAccess ? 'Plano atual' : 'Último plano' }}</p>
                        <h2 class="mt-0.5 text-xl font-bold tracking-tight text-ink">{{ $subscription->plan->name }}</h2>
                    </div>

                    <x-ui.badge :tone="$subscription->statusTone()">{{ $subscription->statusLabel() }}</x-ui.badge>
                </div>

                @if ($message = $subscription->statusMessage())
                    <p class="mt-3 text-sm text-muted">{{ $message }}</p>
                @endif

                @if ($quota)
                    @if ($quota['limit'] === null)
                        <p class="mt-4 text-sm text-muted">Downloads ilimitados de famílias pagas.</p>
                    @else
                        @php($percent = min(100, (int) round($quota['used'] / max(1, $quota['limit']) * 100)))

                        <p class="mt-4 text-sm text-muted">
                            Downloads de famílias pagas neste mês:
                            <strong class="text-ink">{{ $quota['used'] }}</strong> de <strong class="text-ink">{{ $quota['limit'] }}</strong>.
                        </p>

                        <div
                            class="mt-2 h-2.5 overflow-hidden rounded-full bg-canvas"
                            role="progressbar"
                            aria-valuemin="0"
                            aria-valuemax="{{ $quota['limit'] }}"
                            aria-valuenow="{{ min($quota['used'], $quota['limit']) }}"
                        >
                            <div class="h-full rounded-full bg-primary" style="width: {{ $percent }}%"></div>
                        </div>

                        <p class="mt-2 text-xs text-subtle">A cota volta em {{ $quota['resets_at']->format('d/m/Y') }}.</p>
                    @endif
                @endif

                <a href="{{ route('account.subscription') }}" class="mt-4 inline-block text-sm font-medium text-primary underline">
                    Ver assinatura e pagamentos
                </a>
            @else
                <p class="text-sm text-subtle">Plano</p>
                <h2 class="mt-0.5 text-xl font-bold tracking-tight text-ink">Sem plano ativo</h2>
                <p class="mt-2 text-sm text-muted">
                    As famílias gratuitas continuam liberadas. Com um plano, você também baixa as famílias pagas.
                </p>
                <x-ui.button :href="route('revit.families.index')" variant="tonal" class="mt-4">Explorar famílias</x-ui.button>
            @endif
        </section>

        {{-- Shortcuts --}}
        <div class="grid gap-4 sm:grid-cols-2">
            <a href="{{ route('account.downloads') }}" class="group flex items-center gap-4 rounded-card bg-surface p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-lg">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary-soft text-primary">
                    @svg('heroicon-o-arrow-down-tray', 'size-6')
                </span>
                <span>
                    <span class="block text-2xl font-bold leading-none text-ink">{{ number_format($downloadsCount, 0, ',', '.') }}</span>
                    <span class="mt-1 block text-sm text-muted">{{ $downloadsCount === 1 ? 'família baixada' : 'famílias baixadas' }}</span>
                </span>
            </a>

            <a href="{{ route('account.favorites') }}" class="group flex items-center gap-4 rounded-card bg-surface p-5 shadow-card transition hover:-translate-y-0.5 hover:shadow-lg">
                <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary-soft text-primary">
                    @svg('heroicon-o-heart', 'size-6')
                </span>
                <span>
                    <span class="block text-2xl font-bold leading-none text-ink">{{ number_format($favoritesCount, 0, ',', '.') }}</span>
                    <span class="mt-1 block text-sm text-muted">{{ $favoritesCount === 1 ? 'família favorita' : 'famílias favoritas' }}</span>
                </span>
            </a>
        </div>

        {{-- Recent downloads --}}
        <section class="rounded-card bg-surface p-6 shadow-card">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-semibold text-ink">Últimos downloads</h2>

                @if ($recentDownloads->isNotEmpty())
                    <a href="{{ route('account.downloads') }}" class="text-sm font-medium text-primary underline">Ver todos</a>
                @endif
            </div>

            @if ($recentDownloads->isEmpty())
                <p class="mt-3 text-sm text-muted">
                    Você ainda não baixou nenhuma família.
                    <a href="{{ route('revit.families.index') }}" class="font-medium text-primary underline">Explorar famílias</a>
                </p>
            @else
                <ul class="mt-2 divide-y divide-line">
                    @foreach ($recentDownloads as $family)
                        <li class="flex items-center gap-4 py-3">
                            <div class="relative aspect-[4/3] w-16 shrink-0 overflow-hidden rounded-control bg-canvas">
                                @if ($family->thumbnail_url)
                                    <img
                                        src="{{ $family->thumbnail_url }}"
                                        alt="{{ $family->name }} - família Revit"
                                        width="64"
                                        height="48"
                                        loading="lazy"
                                        class="absolute inset-0 size-full object-contain"
                                    >
                                @else
                                    <span class="absolute inset-0 flex items-center justify-center text-subtle">
                                        @svg('heroicon-o-cube', 'size-5')
                                    </span>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-ink">
                                    <a href="{{ route('revit.families.show', ['family' => $family->slug]) }}" class="hover:underline">{{ $family->name }}</a>
                                </p>
                                <p class="truncate text-xs text-subtle">
                                    {{ collect([$family->category?->name, $family->brand?->name])->filter()->implode(' · ') }}
                                </p>
                            </div>

                            <span class="shrink-0 text-xs text-subtle">{{ $family->last_downloaded_at->format('d/m/Y') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Account data --}}
        <section class="rounded-card bg-surface p-6 shadow-card">
            <div class="flex items-center gap-4">
                <x-ui.avatar :user="$user" class="size-14 text-lg" />

                <div class="min-w-0">
                    <p class="truncate font-semibold text-ink">{{ $user->name }}</p>
                    <p class="truncate text-sm text-muted">{{ $user->email }}</p>
                </div>

                <a href="{{ route('account.profile') }}" class="ml-auto shrink-0 text-sm font-medium text-primary underline">Editar perfil</a>
            </div>
        </section>
    </div>
</x-layouts.account>