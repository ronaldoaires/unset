<x-layouts.site
    :title="$selected ? 'Orçamento de ' . $selected->name : 'Solicitar orçamento'"
    description="Peça um orçamento de projeto de engenharia ou de tecnologia. Resposta rápida e sem compromisso, com atendimento para todo o Brasil."
    :canonical="route('quotes.create')">

    @php
    $field = 'block w-full rounded-control border bg-surface px-3.5 py-2 text-base text-ink shadow-sm placeholder:text-subtle focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20';
    $border = fn (string $name) => $errors->has($name) ? 'border-red-500' : 'border-line';
    $selectedId = old('service_id', $selected?->id);
    @endphp

    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-10 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <h1 class="text-3xl font-bold tracking-tight text-ink">
                {{ $selected ? 'Orçamento de ' . mb_strtolower($selected->name) : 'Solicitar orçamento' }}
            </h1>
            <p class="mt-2 text-muted">
                Conte o que você precisa. Respondemos {{ config('showcase.contact.reply_time') }}, sem compromisso.
            </p>

            @if ($project)
            <x-ui.alert tone="info" icon="photo" class="mt-6">
                Você está pedindo um orçamento a partir do projeto <strong>{{ $project->title }}</strong>.
            </x-ui.alert>
            @endif

            <form method="POST" action="{{ route('quotes.store') }}" class="quote-form mt-6 space-y-6 rounded-card bg-surface p-6 shadow-card sm:p-8">
                @csrf
                <x-auth.bot-trap />

                @if ($project)
                <input type="hidden" name="project_id" value="{{ $project->id }}">
                @endif

                <div>
                    <label for="service_id" class="mb-1.5 block text-sm font-medium text-ink">Serviço</label>
                    <select id="service_id" name="service_id" class="{{ $field }} min-h-12 {{ $border('service_id') }}">
                        <option value="">Ainda não sei / outro</option>
                        @foreach ($servicesByArea as $area => $services)
                        <optgroup label="{{ \App\Enums\ServiceArea::from($area)->getLabel() }}">
                            @foreach ($services as $service)
                            <option value="{{ $service->id }}" data-area="{{ $area }}" @selected((string) $selectedId === (string) $service->id)>{{ $service->name }}</option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    @error('service_id')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                {{-- Both groups are in the page; CSS shows the one of the chosen area
                     (app.css, .quote-form) and the server keeps only that one. --}}
                @foreach ($details as $area => $questions)
                <fieldset data-details="{{ $area }}" class="gap-4 rounded-control bg-canvas p-4 sm:grid-cols-3">
                    <legend class="sr-only">Detalhes do projeto</legend>

                    @foreach ($questions as $key => $question)
                    <div>
                        <label for="details-{{ $area }}-{{ $key }}" class="mb-1.5 block text-sm font-medium text-ink">{{ $question['label'] }}</label>

                        @if (isset($question['options']))
                        <select id="details-{{ $area }}-{{ $key }}" name="details[{{ $key }}]" class="{{ $field }} min-h-12 border-line">
                            <option value="">Selecione</option>
                            @foreach ($question['options'] as $option)
                            <option value="{{ $option }}" @selected(old('details.' . $key) === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        @else
                        <input id="details-{{ $area }}-{{ $key }}" name="details[{{ $key }}]" type="number" min="1" max="1000000" inputmode="decimal" value="{{ old('details.' . $key) }}" class="{{ $field }} min-h-12 border-line">
                        @endif
                    </div>
                    @endforeach
                </fieldset>
                @endforeach

                <div class="grid gap-6 sm:grid-cols-2">
                    <x-ui.input name="name" label="Nome" autocomplete="name" maxlength="100" required />
                    <x-ui.input name="email" label="E-mail" type="email" autocomplete="email" maxlength="255" required />
                    <x-ui.input
                        name="phone"
                        label="WhatsApp"
                        type="tel"
                        data-mask="phone"
                        inputmode="numeric"
                        autocomplete="tel-national"
                        placeholder="(66) 99999-9999"
                        required />

                    <div class="grid grid-cols-3 gap-3">
                        <div class="col-span-2">
                            <x-ui.input name="city" label="Cidade" autocomplete="address-level2" maxlength="100" />
                        </div>
                        <div>
                            <label for="state" class="mb-1.5 block text-sm font-medium text-ink">UF</label>
                            <select id="state" name="state" autocomplete="address-level1" class="{{ $field }} min-h-12 {{ $border('state') }}">
                                <option value=""></option>
                                @foreach ($states as $code => $state)
                                <option value="{{ $code }}" title="{{ $state }}" @selected(old('state') === $code)>{{ $code }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <label for="message" class="mb-1.5 block text-sm font-medium text-ink">Conte sobre o projeto</label>
                    <textarea
                        id="message"
                        name="message"
                        rows="6"
                        minlength="10"
                        maxlength="3000"
                        required
                        placeholder="Ex: casa térrea de 120 m² em terreno de 10x25, preciso do projeto estrutural para aprovar na prefeitura."
                        class="{{ $field }} {{ $border('message') }}">{{ old('message') }}</textarea>
                    @error('message')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="flex items-start gap-2.5 text-sm text-muted">
                        <input type="checkbox" name="privacy" value="1" @checked(old('privacy')) required class="mt-0.5 size-5 shrink-0 rounded border-line text-primary focus:ring-primary">
                        <span>Li e aceito a <a href="{{ route('legal.privacy') }}" target="_blank" class="font-medium text-primary underline">Política de Privacidade</a>. Meus dados serão usados só para responder este pedido.</span>
                    </label>
                    @error('privacy')<p class="mt-1.5 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <x-ui.button size="lg" class="w-full sm:w-auto">
                    @svg('heroicon-o-paper-airplane', 'size-5')
                    Enviar pedido de orçamento
                </x-ui.button>
            </form>
        </div>

        <aside class="h-fit space-y-4 lg:sticky lg:top-6">
            <div class="rounded-card bg-surface p-6 shadow-card">
                <h2 class="text-lg font-bold text-ink">Prefere conversar?</h2>
                <p class="mt-1 text-sm text-muted">Fale direto pelo WhatsApp.</p>

                <form method="POST" action="{{ route('whatsapp') }}" target="_blank" data-no-loading class="mt-4">
                    @csrf
                    @if ($selected)<input type="hidden" name="servico" value="{{ $selected->slug }}">@endif
                    @if ($project)<input type="hidden" name="projeto" value="{{ $project->slug }}">@endif
                    <x-ui.button variant="tonal" class="w-full">
                        @svg('heroicon-o-chat-bubble-left-right', 'size-5')
                        Falar no WhatsApp
                    </x-ui.button>
                </form>
            </div>

            <div class="rounded-card bg-surface p-6 shadow-card">
                <h2 class="text-lg font-bold text-ink">O que acontece depois</h2>
                <ol class="mt-4 space-y-3 text-sm text-muted">
                    @foreach (config('showcase.steps') as $step)
                    <li class="flex gap-3">
                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-on-primary">{{ $loop->iteration }}</span>
                        <span><strong class="text-ink">{{ $step['title'] }}:</strong> {{ $step['text'] }}</span>
                    </li>
                    @endforeach
                </ol>
            </div>

            <p class="flex items-start gap-2 px-2 text-sm text-subtle">
                @svg('heroicon-o-shield-check', 'mt-0.5 size-5 shrink-0 text-primary')
                @if ($selected)
                @php($profile = config('showcase.profiles.' . $selected->area->value))
                {{ config('showcase.person.name') }}, {{ $profile['title'] }} · {{ $profile['badge'] }}.
                @else
                {{ config('showcase.person.name') }}, Engenheiro Civil ({{ config('showcase.profiles.engineering.badge') }}) e {{ config('showcase.profiles.technology.title') }}.
                @endif
            </p>
        </aside>
    </div>
</x-layouts.site>
