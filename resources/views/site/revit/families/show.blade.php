<x-layouts.site
    :title="$family->name . ' - Família Revit'"
    :description="$family->description ?: 'Baixe a família Revit ' . $family->name . ' (.rfa) para usar nos seus projetos BIM.'"
    :image="$family->image_url"
>
    <div class="mx-auto max-w-6xl px-4 py-8">
        <nav aria-label="Você está em" class="text-sm text-subtle">
            <a href="{{ route('revit.families.index') }}" class="hover:underline">Famílias Revit</a>
            @if ($family->category)
                <span aria-hidden="true">/</span>
                <span>{{ $family->category->name }}</span>
            @endif
        </nav>

        <div class="mt-4 grid gap-6 lg:grid-cols-5">
            <div class="overflow-hidden rounded-card bg-surface shadow-card lg:col-span-3">
                @if ($family->image_url)
                    <img
                        src="{{ $family->image_url }}"
                        alt="{{ $family->name }} - família Revit"
                        class="aspect-[4/3] w-full object-cover"
                    >
                @endif
            </div>

            <div class="rounded-card bg-surface p-6 shadow-card lg:col-span-2">
                <h1 class="text-2xl font-bold leading-tight tracking-tight text-ink">{{ $family->name }}</h1>

                @if ($family->brand)
                    <p class="mt-1 text-sm text-subtle">{{ $family->brand->name }}</p>
                @endif

                <div class="mt-4 flex flex-wrap gap-2">
                    <span class="rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-on-primary-soft">Revit {{ $family->revit_version }}</span>
                    @if ($family->is_parametric)
                        <span class="rounded-full bg-primary-soft px-3 py-1 text-xs font-semibold text-on-primary-soft">Paramétrica</span>
                    @endif
                </div>

                <p class="mt-6 text-2xl font-bold text-ink">{{ $family->formatted_price }}</p>

                <div class="mt-6">
                    @guest
                        <x-ui.button :href="route('login')" size="lg" class="w-full">Entrar para baixar</x-ui.button>
                    @endguest

                    @auth
                        @php($access = \Illuminate\Support\Facades\Gate::inspect('download', $family))

                        @if ($access->allowed())
                            <x-ui.button :href="route('revit.families.download', ['family' => $family->slug])" size="lg" class="w-full">Baixar família</x-ui.button>
                        @else
                            <p class="rounded-control bg-canvas p-3 text-sm text-muted">{{ $access->message() }}</p>
                        @endif
                    @endauth
                </div>
            </div>
        </div>

        @if ($family->description)
            <section class="mt-6 rounded-card bg-surface p-6 shadow-card">
                <h2 class="text-lg font-semibold text-ink">Sobre esta família</h2>
                <p class="mt-2 max-w-prose whitespace-pre-line leading-relaxed text-muted">{{ $family->description }}</p>

                @if (count($family->tag_list))
                    <div class="mt-4 flex flex-wrap gap-2">
                        @foreach ($family->tag_list as $tag)
                            <span class="rounded-full bg-canvas px-3 py-1 text-xs font-medium text-muted">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif
    </div>
</x-layouts.site>