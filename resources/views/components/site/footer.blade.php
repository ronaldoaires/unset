<footer class="border-t border-line bg-surface">
    <div class="mx-auto flex max-w-6xl flex-col gap-3 px-4 py-8 text-sm text-subtle sm:flex-row sm:items-center sm:justify-between">
        <p>© {{ date('Y') }} {{ config('app.name') }}. Todos os direitos reservados.</p>

        <nav aria-label="Legal" class="flex gap-4">
            <a href="{{ route('legal.terms') }}" class="hover:underline">Termos de Uso</a>
            <a href="{{ route('legal.privacy') }}" class="hover:underline">Política de Privacidade</a>
        </nav>
    </div>
</footer>