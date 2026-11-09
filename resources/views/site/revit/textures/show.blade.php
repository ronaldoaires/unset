@php
    $mapLabels = \App\Models\TextureMap::TYPES;
    $sizeText  = $texture->tile_width_mm . ' × ' . $texture->tile_height_mm . ' mm';
@endphp

<x-layouts.site
    :title="$texture->name . ' - Textura PBR para Revit'"
    :description="$texture->description ?: 'Textura PBR ' . $texture->name . ' no tamanho real (' . $sizeText . '). Baixe grátis ou aplique direto no Revit com o plugin UnSet BIM.'"
    :image="$texture->preview_url"
    :json-ld="$jsonLd">
    <div class="mx-auto max-w-6xl px-4 py-8">
        <x-site.breadcrumb :items="$breadcrumbs" />

        <div class="mt-4 grid gap-6 lg:grid-cols-5">
            <div class="flex flex-col items-center justify-center rounded-card bg-gradient-to-b from-white to-canvas p-8 shadow-card lg:col-span-2">
                @if ($texture->preview_url)
                    {{-- Native <dialog>: Esc closes it for free; a click on the dark area closes it too. --}}
                    <button type="button" class="w-full max-w-sm cursor-zoom-in rounded-full focus:outline-none focus-visible:ring-4 focus-visible:ring-primary/40"
                            onclick="document.getElementById('texture-image').showModal()"
                            aria-label="Ver a imagem plana da textura">
                        <x-site.revit.texture-sphere :texture="$texture" size="large" class="w-full" />
                    </button>

                    <button type="button" onclick="document.getElementById('texture-image').showModal()"
                            class="mt-6 inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline">
                        @svg('heroicon-o-magnifying-glass-plus', 'size-4')
                        Ver a imagem plana
                    </button>

                    <dialog id="texture-image"
                            class="m-auto w-fit max-w-[92vw] overflow-visible bg-transparent p-0 backdrop:bg-black/80"
                            onclick="if (event.target === this) this.close()">
                        <div class="relative">
                            <img src="{{ $texture->preview_url }}" alt="{{ $texture->name }} - textura PBR"
                                 class="mx-auto block max-h-[80vh] w-auto max-w-full rounded-card shadow-2xl" loading="lazy">
                            <button type="button" onclick="this.closest('dialog').close()"
                                    class="absolute -top-3 -right-3 flex size-10 items-center justify-center rounded-full bg-white text-ink shadow-lg transition hover:bg-canvas"
                                    aria-label="Fechar">
                                @svg('heroicon-o-x-mark', 'size-5')
                            </button>
                            <p class="mt-3 text-center text-sm text-white/80">
                                Uma repetição da textura · {{ $sizeText }} no tamanho real
                            </p>
                        </div>
                    </dialog>
                @else
                    <x-site.revit.texture-sphere :texture="$texture" size="large" class="w-full max-w-sm" />
                @endif
            </div>

            <div class="rounded-card bg-surface p-6 shadow-card lg:col-span-3">
                <h1 class="text-2xl font-bold leading-tight tracking-tight text-ink">{{ $texture->name }}</h1>
                @if ($texture->category)
                    <p class="mt-1 text-sm text-subtle">{{ $texture->category->name }}</p>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    @if ($texture->is_free)
                        <span class="rounded-full bg-success-soft px-3 py-1 text-xs font-bold uppercase tracking-wide text-success">Grátis</span>
                    @else
                        <x-ui.premium-badge />
                    @endif
                    <span class="rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-on-primary-soft">{{ $texture->material_type_label }}</span>
                    @if ($texture->is_seamless)
                        <span class="rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-on-primary-soft">Sem emenda</span>
                    @endif
                </div>

                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-subtle">Tamanho real da repetição</dt>
                        <dd class="mt-0.5 font-semibold text-ink">{{ $sizeText }}</dd>
                    </div>
                    <div>
                        <dt class="text-subtle">Mapas</dt>
                        <dd class="mt-0.5 font-semibold text-ink">
                            {{ $texture->maps->pluck('type')->unique()->map(fn($type) => $mapLabels[$type] ?? $type)->join(', ') ?: '—' }}
                        </dd>
                    </div>
                </dl>

                {{-- Best path first: the plugin makes the Revit material by itself. --}}
                <div class="mt-6 rounded-control bg-primary-soft p-4">
                    <p class="text-sm font-semibold text-on-primary-soft">No Revit é mais rápido</p>
                    <p class="mt-1 text-sm text-on-primary-soft/90">
                        Com o plugin UnSet BIM, esta textura vira material do Revit no tamanho real e você pinta as faces em 3 cliques.
                    </p>
                    <x-ui.button :href="route('plugin.download')" size="md" class="mt-3">
                        @svg('heroicon-o-arrow-down-tray', 'size-5')
                        Baixar o plugin grátis
                    </x-ui.button>
                </div>

                <div class="mt-6">
                    <h2 class="text-sm font-semibold text-ink">Baixar os arquivos (ZIP)</h2>

                    @if (! $texture->needsMaps() || $resolutions === [])
                        <p class="mt-2 text-sm text-muted">Este material não tem imagens: use pelo plugin, que monta o material a partir da cor.</p>
                    @else
                        @guest
                            <x-ui.button :href="route('login')" variant="tonal" size="lg" class="mt-3 w-full">Entrar para baixar</x-ui.button>
                        @endguest

                        @auth
                            @php($access = \Illuminate\Support\Facades\Gate::inspect('download', $texture))

                            @if ($access->allowed())
                                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                                    @foreach ($resolutions as $resolution)
                                        <x-ui.button
                                            :href="route('revit.textures.download', ['texture' => $texture->slug, 'resolucao' => $resolution['resolution']])"
                                            variant="tonal">
                                            @svg('heroicon-o-arrow-down-tray', 'size-5')
                                            {{ strtoupper($resolution['resolution']) }}
                                            <span class="font-normal text-on-primary-soft/80">· {{ number_format($resolution['size_bytes'] / 1048576, 1, ',', '.') }} MB</span>
                                        </x-ui.button>
                                    @endforeach
                                </div>
                                <p class="mt-2 text-xs text-subtle">PNG/JPG de cada mapa + texture.json com o tamanho real.</p>
                            @else
                                <x-ui.alert class="mt-3">{{ $access->message() }}</x-ui.alert>
                                <x-ui.button :href="route('home') . '#planos'" variant="tonal" size="lg" class="mt-3 w-full">Ver planos</x-ui.button>
                            @endif
                        @endauth
                    @endif
                </div>
            </div>
        </div>

        @if ($texture->description)
            <section class="mt-6 rounded-card bg-surface p-6 shadow-card">
                <h2 class="text-lg font-semibold text-ink">Sobre esta textura</h2>
                <p class="mt-2 whitespace-pre-line leading-relaxed text-muted">{{ $texture->description }}</p>
            </section>
        @endif
    </div>
</x-layouts.site>
