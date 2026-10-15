@auth
    @if (! auth()->user()->hasVerifiedEmail() && ! request()->routeIs('verification.*'))
        <div class="bg-amber-50 px-4 py-2 text-center text-sm text-amber-900">
            Confirme seu e-mail para ativar sua conta.
            <a href="{{ route('verification.notice') }}" class="font-medium underline">Reenviar link</a>
        </div>
    @endif
@endauth