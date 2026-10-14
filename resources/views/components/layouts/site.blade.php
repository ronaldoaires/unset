@props([
'title' => null,
'description' => null,
'image' => null,
'canonical' => null,
'noindex' => false,
'type' => 'website',
'jsonLd' => null,
'suffix' => true,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-site.seo
        :title="$title"
        :description="$description"
        :image="$image"
        :canonical="$canonical"
        :noindex="$noindex"
        :type="$type"
        :json-ld="$jsonLd"
        :suffix="$suffix" />

    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen flex-col bg-zinc-50 text-zinc-900 antialiased">

    <header class="border-b border-zinc-200 bg-white">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
            <a href="{{ route('home') }}" class="text-lg font-semibold tracking-tight">
                {{ config('app.name') }}
            </a>

            <nav class="flex items-center gap-4 text-sm">
                @auth
                {{-- <details> keeps the menu working without any JavaScript --}}
                <details class="relative">
                    <summary class="cursor-pointer list-none font-medium [&::-webkit-details-marker]:hidden">
                        {{ \Illuminate\Support\Str::before(auth()->user()->name, ' ') }}
                    </summary>
                    <div class="absolute right-0 z-10 mt-2 w-52 rounded-md border border-zinc-200 bg-white py-1 shadow-lg">
                        @if (auth()->user()->isAdmin())
                        <a href="{{ url('/admin') }}" class="block px-4 py-2 hover:bg-zinc-100">Painel administrativo</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-left hover:bg-zinc-100">Sair</button>
                        </form>
                    </div>
                </details>
                @else
                <a href="{{ route('login') }}" class="font-medium hover:underline">Entrar</a>
                <a href="{{ route('register') }}" class="rounded-md bg-amber-500 px-3 py-1.5 font-semibold text-zinc-900 hover:bg-amber-400">
                    Criar conta
                </a>
                @endauth
            </nav>
        </div>
    </header>

    @auth
    @if (! auth()->user()->hasVerifiedEmail() && ! request()->routeIs('verification.*'))
    <div class="bg-amber-50 px-4 py-2 text-center text-sm text-amber-900">
        Confirme seu e-mail para ativar sua conta.
        <a href="{{ route('verification.notice') }}" class="font-medium underline">Reenviar link</a>
    </div>
    @endif
    @endauth

    @if (session('status'))
    <div class="mx-auto mt-4 w-full max-w-6xl px-4">
        <div class="rounded-md bg-green-50 p-3 text-sm text-green-800" role="status">
            {{ session('status') }}
        </div>
    </div>
    @endif

    <main class="flex-1">
        {{ $slot }}
    </main>

    <footer class="border-t border-zinc-200 bg-white">
        <div class="mx-auto max-w-6xl px-4 py-6 text-sm text-zinc-500">
            © {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.
        </div>
    </footer>
</body>

</html>