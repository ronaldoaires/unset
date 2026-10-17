<x-layouts.account title="Perfil">
    <x-account.page-header
        title="Perfil"
        description="Atualize suas informações pessoais." />

    <form method="POST" action="{{ route('account.profile.update') }}" class="space-y-6 rounded-card bg-surface p-6 shadow-card">
        @csrf
        @method('PUT')

        <x-ui.input name="name" label="Nome" :value="$user->name" autocomplete="name" required />

        <x-ui.input
            name="phone"
            label="Telefone / WhatsApp"
            type="tel"
            :value="$user->phone"
            data-mask="phone"
            inputmode="numeric"
            autocomplete="tel-national"
            placeholder="(66) 99999-9999"
        />

        <div>
            <p class="text-sm font-medium text-ink">E-mail</p>
            <p class="mt-1.5 text-sm text-muted">{{ $user->email }}</p>
        </div>

        <x-ui.button class="w-full sm:w-auto">Salvar alterações</x-ui.button>
    </form>
</x-layouts.account>